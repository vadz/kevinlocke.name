// Conversion of N,E to Lat Long assumes UTM Zone 11 NAD83
// Formulas from http://www.uwgb.edu/dutchs/UsefulData/UTMFormulas.HTM
function UTMtoLatLng(northing, easting) {
	const a = 6378137;		// Equitorial Radius (m)
	const b = 6356752.3142;		// Polar Radius (m)
	const lng0 = (12-1)*6-180+3;	// Central Meridian for zone 12
	const k0 = 0.9996;		// Scale along long0
	const e2 = 1-(b*b)/(a*a);	// Eccentricity^2
	const ep2 = e2/(1-e2);		// e'^2
	const n = (a-b)/(a+b);

	var x = easting - 500000;
	var y = northing;

	var M = y/k0;
	var mu = M/(a*(1-e2/4-3*e2*e2/64-5*e2*e2*e2/256));
	var e1 = (1 - Math.sqrt(1-e2))/(1 + Math.sqrt(1-e2));

	var J1 = 3*e1/2 - 27*e1*e1*e1/32;
	var J2 = 21*e1*e1/16 - 55*e1*e1*e1*e1/32;
	var J3 = 151*e1*e1*e1/96;
	var J4 = 1097*e1*e1*e1*e1/512;
	var fp = mu + J1*Math.sin(2*mu) + J2*Math.sin(4*mu) +
		      J3*Math.sin(6*mu) + J4*Math.sin(8*mu);

	var C1 = ep2*Math.pow(Math.cos(fp),2);
	var T1 = Math.pow(Math.tan(fp),2);
	var R1 = a*(1-e2)/Math.pow(1-e2*Math.pow(Math.sin(fp),2),1.5);
	var N1 = a/Math.sqrt(1-e2*Math.pow(Math.sin(fp),2));
	var D = x/(N1*k0);

	var Q1 = N1*Math.tan(fp)/R1;
	var Q2 = D*D/2;
	var Q3 = (5 + 3*T1 + 10*C1 - 4*C1*C1 - 9*ep2)*D*D*D*D/24;
	var Q4 = (61 + 90*T1 + 298*C1 + 45*T1*T1 - 3*C1*C1 - 252*ep2)*D*D*D*D*D*D/720;
	var Q5 = D;
	var Q6 = (1 + 2*T1 + C1)*D*D*D/6;
	var Q7 = (5 - 2*C1 + 28*T1 - 3*C1*C1 + 8*ep2 + 24*T1*T1)*D*D*D*D*D/120;

	var lat = (fp - Q1*(Q2 - Q3 + Q4))*180/Math.PI;
	var lng = lng0 + ((Q5 - Q6 + Q7)/Math.cos(fp))*180/Math.PI;

	return lat, lng;
}
