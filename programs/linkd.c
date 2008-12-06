/* Linkd:  Motorola SB4100 Link Daemon
 *
 * Copyright (c) 2006, Kevin Locke <kwl7@cornell.edu>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice,
 *   this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 * - The name of the author may be used to endorse or promote products derived
 *   from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * Micro-Changelog:
 *  0.02	Improved command-line option processing
 *  0.01	Initial Release
 */

#define PROGRAM_NAME "linkd (Motorola SB4100 Link Daemon)"
#define PROGRAM_VERSION 0.02

#include <arpa/inet.h>
#include <errno.h>
#include <error.h>
#include <getopt.h>
#include <netinet/in.h>
#include <signal.h>
#include <sys/socket.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <syslog.h>
#include <sys/types.h>
#include <sys/wait.h>
#include <unistd.h>

#define CHECK_INTERVAL 5	/* How often to check network rate (in sec.) */
#define PING_INTERVAL 300	/* Time between pings when below thresh. */
#define NET_RATE_THRESH 20480	/* Minimum amount of traffic in 1 interval */
#define NET_DEV_FILE "/proc/net/dev"
#define NET_INTERFACE "eth0"
#define PING_COMMAND "/bin/ping"
/* The arguments to PING_COMMAND to check internet connectivity
 * Note: target must not requre a DNS lookup (IPs are safe) */
#define PING_ARGS "ping", "-w5", "-c1", "64.233.167.99", NULL
#define MODEM_IP "192.168.100.1"
#define MODEM_RESTART_TIME 120	/* Time after restart cmd to resume checks */

sig_atomic_t keepRunning;	/* Should the processs keep running */
int isdaemon = 0;		/* Is the process running as a daemon */
char *prog_name;

void msglog(int priority, char *message);
unsigned long int received_bytes(void);
int link_is_up(void);
void restart_cable_modem(void);

/* Sets the program to stop running on SIGTERM or SIGINT */
void signal_handler(int signum) {
	if (signum == SIGTERM || signum == SIGINT)
		keepRunning = 0;
}

/* Send program into the background and do bookwork to run as a daemon */
void daemonize(void) {
	pid_t pid, sid;

	/* Fork into the background */
	pid = fork();
	if (pid < 0) {
		exit(EXIT_FAILURE);
	} else if (pid > 0) {
		exit(EXIT_SUCCESS);
	}
	
	/* Create a new session */
	sid = setsid();
	if (sid < 0) {
		exit(EXIT_FAILURE);
	}

	/* Change to a known stable directory */
	if ((chdir("/")) < 0) {
		exit(EXIT_FAILURE);
	}

	/* Close file descriptors, since we have no terminal anymore */
	fclose(stdin);
	fclose(stdout);
	fclose(stderr);

	/* Prepare for logging output */
	openlog(prog_name, LOG_PID, LOG_DAEMON);
}

/* Print usage message to dest */
void print_usage(FILE *dest) {
	fprintf(dest, "Usage:  %s [options]\n", prog_name);
	fprintf(dest, "  %s supports the following options:\n", prog_name);
	fprintf(dest, "  -d, --daemon\trun as a daemon (in the background)\n");
	fprintf(dest, "  -h, --help\tprint this help message\n");
	fprintf(dest, "  -V, --version\tprint version and license info.\n");
}

/* Print version and license information to dest */
void print_version(FILE *dest) {
	fprintf(dest, "%s version %.2f\n", PROGRAM_NAME, PROGRAM_VERSION);
	fprintf(dest, "Compiled at %s on %s\n", __TIME__, __DATE__);
}

int main(int argc, char **argv) {
	unsigned int sleeptime;
	unsigned long int rate;
	int c, longind;
	struct option longops[] = {{"daemon", no_argument, 0, 'd'},
	                           {"daemonize", no_argument, 0, 'd'},
				   {"help", no_argument, 0, 'h'},
				   {"version", no_argument, 0, 'V'},
				   {0, 0, 0, 0}};

	keepRunning = 1;

	/* Set prog_name to the invocation name for error reporting */
	if ((prog_name = strrchr(argv[0], '/')) == NULL)
		prog_name = argv[0];	/* No slashes in argv[0] */
	else
		prog_name++;		/* Set to character after '/' */

	/* Check if we were run as root, which is probably not a good idea */
	if (getuid() == 0) {
		fprintf(stderr, "WARNING:  Running %s as root is insecure!\n",
			prog_name);
	}

	while ((c = getopt_long(argc, argv, "dhV", longops, &longind)) != -1)
		switch (c) {
			case 0:
				/* Long option w/o corresponding short opt */
				break;
			case 'd':
				isdaemon = 1;
				break;
			case 'h':
				print_usage(stdout);
				exit(EXIT_SUCCESS);
			case 'V':
				print_version(stdout);
				exit(EXIT_SUCCESS);
			case '?':
				print_usage(stderr);
				exit(EXIT_FAILURE);
			default:
				fprintf(stderr, "Error parsing options.\n");
				exit(EXIT_FAILURE);
		}
	
	if (isdaemon)
		daemonize();

	signal(SIGTERM, signal_handler);
	signal(SIGINT, signal_handler);

	while (keepRunning) {
		rate = received_bytes();
		if (rate < NET_RATE_THRESH ) {
/*			msglog(LOG_INFO, "Network rate below threshold.\n"); */
			if (!link_is_up()) {
				msglog(LOG_INFO, "Network is down.\n");
				restart_cable_modem();
				msglog(LOG_INFO, "Restart complete.\n");
			} else {
				/* The network is up and not being used,
				 * sleep for a while, then look again */
				sleeptime = PING_INTERVAL;
				while (keepRunning && 
				       (sleeptime = sleep(sleeptime)) != 0);
			}
		}

		sleeptime = CHECK_INTERVAL;
		while (keepRunning && (sleeptime = sleep(sleeptime)) != 0);
	}

	return 0;
}

/* Write a message to the system log if daemonized, or stdout/err if not */
void msglog(int priority, char *message) {
	FILE *msgfile;

	if (isdaemon)
		syslog(priority, message);
	else {
		switch (priority) {
			case LOG_EMERG:
			case LOG_ALERT:
			case LOG_CRIT:
			case LOG_ERR:
			case LOG_WARNING:
				msgfile = stderr;
				break;
			default:
				msgfile = stdout;
		}

		fprintf(msgfile, "%s: %s", prog_name, message);
	}
}

/* returns the number of bytes received since last called
 * Note:  On first call, returns number of bytes since interface brought up */
unsigned long int received_bytes(void) {
	static unsigned long int prevbytes = 0; /* bytes last call */
	long int currbytes = 0; 		/* bytes this call */
	unsigned long int bytesreceived = 0;	/* bytes since last call */
	FILE *devfile;
	int linesize = 200;
	char *line, *devline;

	if ((devfile = fopen(NET_DEV_FILE, "r")) == NULL) {
		msglog(LOG_ERR, "Unable to open network devices file.\n");
		msglog(LOG_ERR, strerror(errno));
		exit(EXIT_FAILURE);
	} else if ((line = (char *)malloc(linesize*sizeof(char))) == NULL) {
		msglog(LOG_ERR, "Unable to allocate memory for buffer.\n");
		msglog(LOG_ERR, strerror(errno));
		exit(EXIT_FAILURE);
	}

	while (!feof(devfile)) {
		if (fgets(line, linesize, devfile) == NULL) {
			msglog(LOG_ERR, "Error reading net devices file.\n");
			msglog(LOG_ERR, strerror(errno));
			exit(EXIT_FAILURE);
		}
		
		devline = strstr(line, NET_INTERFACE);
		/* If strstr found a match, we are done */
		if (devline != NULL) {
			currbytes = strtoul(devline+strlen(NET_INTERFACE)+1,
			                    NULL, 10);

			/* FIXME:  Should we be checking for wrap here? */
			bytesreceived = currbytes - prevbytes;
			prevbytes = currbytes;
			fclose(devfile);
			return bytesreceived;
		}
	}

	msglog(LOG_ERR, "Unable to find interface \"" NET_INTERFACE " in "
			NET_DEV_FILE ".\n");
	exit(EXIT_FAILURE);
}

/* See if we can still talk to the internet using a ping */
int link_is_up(void) {
	pid_t pingpid;
	int pingstatus;

	pingpid = fork();
	if (pingpid == -1) {
		msglog(LOG_ERR, "Unable to fork in order to ping.\n");
		msglog(LOG_ERR, strerror(errno));
		exit(EXIT_FAILURE);
	} else if (pingpid == 0) {
		/* In child process */
		fclose(stdin);
		fclose(stdout);
		fclose(stderr);
		/* FIXME: Is there anything to worry about in the environment */
		if (execl(PING_COMMAND, PING_ARGS) == -1) {
			msglog(LOG_ERR, "Unable to run " PING_COMMAND ".\n");
			msglog(LOG_ERR, strerror(errno));
			exit(EXIT_FAILURE);
		}
	} else {
		/* In parent process */
		if (waitpid(pingpid, &pingstatus, 0) == -1) {
			msglog(LOG_ERR, "Waiting for ping failed.\n");
			msglog(LOG_ERR, strerror(errno));
			exit(EXIT_FAILURE);
		}

		if (WIFEXITED(pingstatus))
			/* If ping exited with status 0, the link is up */
			return WEXITSTATUS(pingstatus) == 0;
		else
			/* If ping did not exit normally, the link is probably
			 * down (ping seems to kill itself badly when
			 * connections get funky) */
			return 0;
	}

	return -1;
}

/* Sends HTTP Post data to the cable modem to cause a reset */
void restart_cable_modem(void) {
	unsigned int sleeptime;
	int sock, connfailures = 0;
	struct sockaddr_in servername;
	char message[] = "POST /configdata.html HTTP/1.1\r\n"
	                 "Host: " MODEM_IP "\r\n"
			 "Referer: http://" MODEM_IP "/configdata.html\r\n"
			 "Content-Type: application/x-www-form-urlencoded\r\n"
			 "Content-Length: 32\r\n"
			 "\r\n"
			 "BUTTON_INPUT=Restart+Cable+Modem";
	
	sock = socket(PF_INET, SOCK_STREAM, 0);
	if (sock < 0) {
		msglog(LOG_ERR, strerror(errno));
		exit(EXIT_FAILURE);
	}

	servername.sin_family = AF_INET;
	servername.sin_port = htons(80);
	inet_aton("192.168.100.1", &(servername.sin_addr));

	while (keepRunning && connect(sock, (struct sockaddr *)&servername,
		                      sizeof(servername)) < 0) {
		/* If modem is not responding, sleep and try again */
		if (errno == EAGAIN || errno == ETIMEDOUT) {
			if (connfailures++ == 0)
				msglog(LOG_ERR, "Error connecting to modem.  "
				                "Retrying...\n");
			sleeptime = MODEM_RESTART_TIME;
			while (keepRunning && 
			       (sleeptime = sleep(sleeptime)) != 0);
		} else {
			msglog(LOG_ERR, strerror(errno));
			exit(EXIT_FAILURE);
		}

		/* Check for connectivity (in case user forced a restart) */
		if (link_is_up())
			return;
	}

	/* Send a message to the server to restart */
	write(sock, message, sizeof(message)/sizeof(message[0]));
	close(sock);

	/* Give the cable modem time to restart */
	sleeptime = MODEM_RESTART_TIME;
	while (keepRunning && (sleeptime = sleep(sleeptime)) != 0);
}
