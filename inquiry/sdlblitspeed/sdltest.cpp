/// \file test.cpp Source file for the SDL Test program

#include <cstdlib>
#include <cstdio>

#include "SDL.h"
#include "SDL_image.h"

#define ARRAY_SIZE(x) ((sizeof x) / (sizeof *x))

static const int NUM_DRAWS = 1000;
static const int DISPLAY_WIDTH = 800;
static const int DISPLAY_HEIGHT = 600;
static const int DISPLAY_DEPTH = 32;

static const Uint32 testedscreenflags[] = {
	0,
	SDL_FULLSCREEN,
	SDL_HWSURFACE,
	SDL_FULLSCREEN | SDL_HWSURFACE,
	// Can't have SDL_DOUBLEBUF without SDL_HWSURFACE
	SDL_HWSURFACE | SDL_DOUBLEBUF,
	SDL_FULLSCREEN | SDL_HWSURFACE | SDL_DOUBLEBUF
};

static const Uint32 testedimageflags[] = {
	0,
	SDL_HWSURFACE,
	SDL_SRCCOLORKEY,
	SDL_SRCCOLORKEY | SDL_HWSURFACE,
	SDL_SRCCOLORKEY | SDL_RLEACCEL,
	SDL_SRCCOLORKEY | SDL_RLEACCEL | SDL_HWSURFACE,
	SDL_SRCALPHA,
	SDL_SRCALPHA | SDL_HWSURFACE,
};

char *print_flags_quoted(Uint32 flags)
{
	// Make sure this is always longer than the longest string possible!
	static char flagstring[256] = {'\"', '\0'};

	int printed = 1;
	if (flags & SDL_FULLSCREEN) {
		strcpy(flagstring+printed, "SDL_FULLSCREEN");
		printed += 14;
	}

	if (flags & SDL_HWSURFACE) {
		if (printed != 1) {
			strcpy(flagstring+printed, "|");
			printed++;
		}
		strcpy(flagstring+printed, "SDL_HWSURFACE");
		printed += 13;
	}

	if (flags & SDL_DOUBLEBUF) {
		if (printed != 1) {
			strcpy(flagstring+printed, "|");
			printed++;
		}
		strcpy(flagstring+printed, "SDL_DOUBLEBUF");
		printed += 13;
	}

	if (flags & SDL_ASYNCBLIT) {
		if (printed != 1) {
			strcpy(flagstring+printed, "|");
			printed++;
		}
		strcpy(flagstring+printed, "SDL_ASYNCBLIT");
		printed += 13;
	}

	if (flags & SDL_HWACCEL) {
		if (printed != 1) {
			strcpy(flagstring+printed, "|");
			printed++;
		}
		strcpy(flagstring+printed, "SDL_HWACCEL");
		printed += 11;
	}

	if (flags & SDL_SRCCOLORKEY) {
		if (printed != 1) {
			strcpy(flagstring+printed, "|");
			printed++;
		}
		strcpy(flagstring+printed, "SDL_SRCCOLORKEY");
		printed += 15;
	}

	if (flags & SDL_SRCALPHA) {
		if (printed != 1) {
			strcpy(flagstring+printed, "|");
			printed++;
		}
		strcpy(flagstring+printed, "SDL_SRCALPHA");
		printed += 12;
	}

	if (flags & SDL_RLEACCEL) {
		if (printed != 1) {
			strcpy(flagstring+printed, "|");
			printed++;
		}
		strcpy(flagstring+printed, "SDL_RLEACCEL");
		printed += 12;
	}

	if (printed == 1) {
		strcpy(flagstring+printed, "0");
		printed++;
	}

	strcpy(flagstring+printed, "\"");

	return flagstring;
}

/**
 * \return Number of milliseconds required to draw */
Uint32 drawTest(SDL_Surface *screen, SDL_Surface *image)
{
	// Clear screen to blue so we can see if things are wrong
	SDL_FillRect(screen, NULL, screen->format->Bmask);

	// Blit once before timing so that any pending conversions are done
	SDL_BlitSurface(image, NULL, screen, NULL);
	SDL_Flip(screen);

	// Wait for everything to settle down (video mode switch)
	SDL_Delay(100);

	Uint32 startticks = SDL_GetTicks();
	for (int i=0; i<NUM_DRAWS; i++) {
		SDL_BlitSurface(image, NULL, screen, NULL);
		SDL_Flip(screen);
	}
	Uint32 endticks = SDL_GetTicks();
	return endticks - startticks;
}

/**
 * Convert a surface to have the given flags and the same format as the
 * display surface.
 */
SDL_Surface *convert_surface(SDL_Surface *origimage, Uint32 imageflags)
{
	SDL_Surface *image;
	if (imageflags & SDL_SRCALPHA) {
		// Take special action to preserve alpha values
		// WARNING:  RGBA->RGBA blits do not do what you think they do!
		
		image = SDL_DisplayFormatAlpha(origimage);
	} else if (imageflags & SDL_SRCCOLORKEY) {
		image = SDL_CreateRGBSurface(imageflags, DISPLAY_WIDTH, DISPLAY_HEIGHT,
					DISPLAY_DEPTH, 0, 0, 0, 0);

		if (!image) {
			fprintf(stderr, "Unable to create surface:  %s\n", SDL_GetError());
			exit(EXIT_FAILURE);
		}

		// Fill with color to be keyed, then key it
		Uint32 fill = SDL_MapRGB(image->format, 0, 255, 0);
		if (SDL_FillRect(image, NULL, fill) < 0) {
			fprintf(stderr, "Unable to fill image:  %s\n", SDL_GetError());
			exit(EXIT_FAILURE);
		}

		if (SDL_BlitSurface(origimage, NULL, image, NULL) < 0) {
			fprintf(stderr, "Unable to blit surface:  %s\n", SDL_GetError());
			exit(EXIT_FAILURE);
		}

		if (SDL_SetColorKey(image, imageflags, fill) < 0) {
			fprintf(stderr, "Unable to set color key:  %s\n", SDL_GetError());
			exit(EXIT_FAILURE);
		}
	} else {
		image = SDL_DisplayFormat(origimage);

		// Turn off SRCALPHA, since it is set to opaque anyway
		if (image)
			image->flags &= ~SDL_SRCALPHA;
	}

	if (!image) {
		fprintf(stderr, "Unable to create surface:  %s\n", SDL_GetError());
		exit(EXIT_FAILURE);
	}

	return image;
}

void runTests(const Uint32 *screenflags, int numscreens,
		const Uint32 *imageflags, int numimageflags,
		SDL_Surface *origimage)
{
	for (int i=0; i<numscreens; i++) {
		SDL_Surface *screen = SDL_SetVideoMode(DISPLAY_WIDTH, DISPLAY_HEIGHT,
				DISPLAY_DEPTH, screenflags[i] | SDL_ANYFORMAT);

		if (!screen) {
			fprintf(stderr, "Error setting video mode:  %s\n", SDL_GetError());
			printf("# Video mode %s not supported\n",
					print_flags_quoted(screenflags[i]));
			// Flush it in case we crash on mode switch
			fflush(stdout);
			continue;
		}

		// Note:  fbcon sets SDL_NOFRAME, but we don't really care about it
		//		  directfb sets SDL_PREALLOC, but that is also ok
		if ((screen->flags & ~(SDL_NOFRAME|SDL_PREALLOC)) != screenflags[i]) {
			printf("# Video mode %s not supported (differing flags %X)\n",
					print_flags_quoted(screenflags[i]),
					screenflags[i] ^ screen->flags);
			// Flush it in case we crash on mode switch
			fflush(stdout);
			continue;
		}

		printf("%45s", print_flags_quoted(screenflags[i]));

		for (int j=0; j<numimageflags; j++) {
			SDL_Surface *image = convert_surface(origimage, imageflags[j]);

			// Draw first, since RLE is not fixed until drawing
			// Ignore SDL_RLEACCELOK, SDL_PREALLOC, and SDL_HWACCEL used internally by SDL
			Uint32 ticks = drawTest(screen, image);
			if ((image->flags & ~(SDL_RLEACCELOK|SDL_PREALLOC|SDL_HWACCEL)) != imageflags[j]) {
				fprintf(stderr, "Error setting image flags %s "
						"(differing flags %X)\n",
						print_flags_quoted(imageflags[j]),
						image->flags ^ imageflags[j]);
				printf("%45s", "-");
			} else {
				printf("%45g", (double)NUM_DRAWS/ticks*1000);
			}

			SDL_FreeSurface(image);
		}

		if (screen->format->BitsPerPixel < DISPLAY_DEPTH)
			printf("# Video mode %s only supported at %d bpp\n",
					print_flags_quoted(screenflags[i]),
					screen->format->BitsPerPixel);

		printf("\n");

		// Flush it in case we crash on mode switch
		fflush(stdout);
	}
}

void handleEvents(void)
{
	// Handle pending events
	SDL_Event event;
	while (SDL_PollEvent(&event)) {
		switch (event.type) {
			case SDL_QUIT:
				fprintf(stderr, "Terminated by user.\n");
				exit(EXIT_SUCCESS);
				break;
		}
	}
}


int main(int argc, char **argv)
{
	if (argc != 2) {
		fprintf(stderr, "Usage:  %s <testimage>\n", argv[0]);
		exit(EXIT_FAILURE);
	}

	if (SDL_Init(SDL_INIT_VIDEO | SDL_INIT_TIMER) == -1) {
		fprintf(stderr, "Error initializing SDL:  %s\n", SDL_GetError());
		exit(EXIT_FAILURE);
	}

	// Cleanup SDL when we exit
	atexit(SDL_Quit);

	SDL_WM_SetCaption("Blit Speed Test", "Blit Speed Test");

	const SDL_version *version = SDL_Linked_Version();
	fprintf(stderr, "Using libSDL version %d.%d.%d\n",
			version->major, version->minor, version->patch);

	// Must set video mode before surface conversions
	SDL_SetVideoMode(DISPLAY_WIDTH, DISPLAY_HEIGHT, DISPLAY_DEPTH, SDL_HWSURFACE);

	SDL_Surface *image = IMG_Load(argv[1]);
	if (!image) {
		fprintf(stderr, "Error loading image:  %s\n", IMG_GetError());
		exit(EXIT_FAILURE);
	} else if (image->w != DISPLAY_WIDTH || image->h != DISPLAY_HEIGHT) {
		fprintf(stderr, "WARNING:  Image dimensions do not match display.\n"
				"Please use 800x600 images for accurate test results.\n");
	}

	{
		char drivername[1024];
		SDL_VideoDriverName(drivername, 1024);
		printf("# Test data for the %s video driver (fps)\n", drivername);
	}

	// Need to figure out which surface flags are supported since GNUPlot
	// refuses to render histogram with columns containing all NODATA values
	SDL_Surface *test = SDL_CreateRGBSurface(SDL_HWSURFACE|SDL_ANYFORMAT, DISPLAY_WIDTH, DISPLAY_HEIGHT,
			DISPLAY_DEPTH, 0, 0, 0, 0);

	Uint32 imageflags[ARRAY_SIZE(testedimageflags)];
	int numimageflags = 0;
	if (test == NULL || !(test->flags & SDL_HWSURFACE)) {
		printf("# Unable to create hardware surfaces\n");

		for (unsigned int i=0; i<ARRAY_SIZE(testedimageflags); i++)
			if (!(testedimageflags[i] & SDL_HWSURFACE))
				imageflags[numimageflags++] = testedimageflags[i];
	} else  {
		numimageflags = ARRAY_SIZE(testedimageflags);
		for (unsigned int i=0; i<ARRAY_SIZE(testedimageflags); i++)
			imageflags[i] = testedimageflags[i];
	}

	printf("%45s", "\"Surface Flags\"");
	for (int i=0; i<numimageflags; i++)
		printf("%45s", print_flags_quoted(imageflags[i]));
	printf("\n");

	runTests(testedscreenflags, ARRAY_SIZE(testedscreenflags),
			imageflags, numimageflags, image);

	return EXIT_SUCCESS;
}

// vim: set ts=4 sts=4 sw=4 noet:
