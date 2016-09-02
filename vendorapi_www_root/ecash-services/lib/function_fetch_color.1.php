<?php
// fetch_color.php
// written by David Bryant
// January 27, 2006

/*

Usage:

	For standard HTML color manipulation:

		$color = fetch_color ([color name string]);

		If color name string is valid, returns:
			$color->hex
			$color->shex (shadow hex: base color - default 36 in each channel normalized to legal range)
			$color->hhex (hilight hex: base color + default 36 in each channel normalized to legal range)
			$color->r (decimal red: red channel 0 to 255)
			$color->sr (shadow red: decimal red - default 36 normalized to legal range)
			$color->hr (hilight red: decimal red + default 36 normalized to legal range
			$color->g (decimal green: green channel 0 to 255)
			$color->sg (shadow green: decimal green - default 36 normalized to legal range)
			$color->hg (hilight green: decimal green + default 36 normalized to legal range)
			$color->b (decimal blue: blue channel 0 to 255)
			$color->sb (shadow blue: decimal blue - default 36 normalized to legal range)
			$color->hb (hilight blue: decimal blue + default 36 normalized to legal range)

		If color name string is the word "list", returns:
			a string of divs containing spans displaying the shadow, base and hilight colors,
			along with the color names.  echo it out to view.

		If color name string is invalid or not "list", returns boolean false

		To alter the shadow and hilight delta (default is 36):

			$color = fetch_color ([color name string], false, false, [positive delta integer 0 to 255]);

	For GD library color manipulation:

		[gd image object] = imagecreatetruecolor (50, 50);
		$color = fetch_color ([color name string], [gd image object]);

		If color name string is valid, calls imagecolorallocate and returns the resulting
		integer or -1 on failure.

		If transparency is required:

			$color = fetch_color ([color name string], [gd image object], [alpha integer 0 to 127]);

			This calls imagecolorallocatealpha and returns the resulting integer
			or -1 on failure.

		If a shadow or hilight color is required:

			$color = fetch_color ([color name string], [gd image object], [alpha integer or boolean false], [delta integer -255 to 255]);

			To obtain a darker color, use a negative integer; for a lighter
			color use a positive integer.  Valid range is -255 to 255.

			Internally, when a GD library image object is being manipulated and delta is applied,
			the function uses the hilight color.  Thus a negative number is used to generate
			darker colors.

		If color name string is invalid, returns boolean false.

		If color name is the word "list" and a GD library object is also passed, the object is
		ignored and the string described above is returned.

*/

function fetch_color ($color_name, $image_obj=false, $image_alpha = false, $delta=false)
{
	$colors = array ();

	$colors['aliceblue'] = new stdClass ();
	$colors['aliceblue']->hex = '#F0F8FF';
	$colors['aliceblue']->r = 240;
	$colors['aliceblue']->g = 248;
	$colors['aliceblue']->b = 255;

	$colors['antiquewhite'] = new stdClass ();
	$colors['antiquewhite']->hex = '#FAEBD7';
	$colors['antiquewhite']->r = 250;
	$colors['antiquewhite']->g = 235;
	$colors['antiquewhite']->b = 215;

	$colors['aqua'] = new stdClass ();
	$colors['aqua']->hex = '#00FFFF';
	$colors['aqua']->r = 0;
	$colors['aqua']->g = 255;
	$colors['aqua']->b = 255;

	$colors['aquamarine'] = new stdClass ();
	$colors['aquamarine']->hex = '#7FFFD4';
	$colors['aquamarine']->r = 127;
	$colors['aquamarine']->g = 255;
	$colors['aquamarine']->b = 212;

	$colors['azure'] = new stdClass ();
	$colors['azure']->hex = '#F0FFFF';
	$colors['azure']->r = 240;
	$colors['azure']->g = 255;
	$colors['azure']->b = 255;

	$colors['beige'] = new stdClass ();
	$colors['beige']->hex = '#F5F5DC';
	$colors['beige']->r = 245;
	$colors['beige']->g = 245;
	$colors['beige']->b = 220;

	$colors['bisque'] = new stdClass ();
	$colors['bisque']->hex = '#FFE4C4';
	$colors['bisque']->r = 255;
	$colors['bisque']->g = 228;
	$colors['bisque']->b = 196;

	$colors['black'] = new stdClass ();
	$colors['black']->hex = '#000000';
	$colors['black']->r = 0;
	$colors['black']->g = 0;
	$colors['black']->b = 0;

	$colors['blanchedalmond'] = new stdClass ();
	$colors['blanchedalmond']->hex = '#FFEBCD';
	$colors['blanchedalmond']->r = 255;
	$colors['blanchedalmond']->g = 235;
	$colors['blanchedalmond']->b = 205;

	$colors['blue'] = new stdClass ();
	$colors['blue']->hex = '#0000FF';
	$colors['blue']->r = 0;
	$colors['blue']->g = 0;
	$colors['blue']->b = 255;

	$colors['blueviolet'] = new stdClass ();
	$colors['blueviolet']->hex = '#8A2BE2';
	$colors['blueviolet']->r = 138;
	$colors['blueviolet']->g = 43;
	$colors['blueviolet']->b = 226;

	$colors['brown'] = new stdClass ();
	$colors['brown']->hex = '#A52A2A';
	$colors['brown']->r = 165;
	$colors['brown']->g = 42;
	$colors['brown']->b = 42;

	$colors['burlywood'] = new stdClass ();
	$colors['burlywood']->hex = '#DEB887';
	$colors['burlywood']->r = 222;
	$colors['burlywood']->g = 184;
	$colors['burlywood']->b = 135;

	$colors['cadetblue'] = new stdClass ();
	$colors['cadetblue']->hex = '#5F9EA0';
	$colors['cadetblue']->r = 95;
	$colors['cadetblue']->g = 158;
	$colors['cadetblue']->b = 160;

	$colors['chartreuse'] = new stdClass ();
	$colors['chartreuse']->hex = '#7FFF00';
	$colors['chartreuse']->r = 127;
	$colors['chartreuse']->g = 255;
	$colors['chartreuse']->b = 0;

	$colors['chocolate'] = new stdClass ();
	$colors['chocolate']->hex = '#D2691E';
	$colors['chocolate']->r = 210;
	$colors['chocolate']->g = 105;
	$colors['chocolate']->b = 30;

	$colors['coral'] = new stdClass ();
	$colors['coral']->hex = '#FF7F50';
	$colors['coral']->r = 255;
	$colors['coral']->g = 127;
	$colors['coral']->b = 80;

	$colors['cornflowerblue'] = new stdClass ();
	$colors['cornflowerblue']->hex = '#6495ED';
	$colors['cornflowerblue']->r = 100;
	$colors['cornflowerblue']->g = 149;
	$colors['cornflowerblue']->b = 237;

	$colors['cornsilk'] = new stdClass ();
	$colors['cornsilk']->hex = '#FFF8DC';
	$colors['cornsilk']->r = 255;
	$colors['cornsilk']->g = 248;
	$colors['cornsilk']->b = 220;

	$colors['crimson'] = new stdClass ();
	$colors['crimson']->hex = '#DC143C';
	$colors['crimson']->r = 220;
	$colors['crimson']->g = 20;
	$colors['crimson']->b = 60;

	$colors['cyan'] = new stdClass ();
	$colors['cyan']->hex = '#00FFFF';
	$colors['cyan']->r = 0;
	$colors['cyan']->g = 255;
	$colors['cyan']->b = 255;

	$colors['darkblue'] = new stdClass ();
	$colors['darkblue']->hex = '#00008B';
	$colors['darkblue']->r = 0;
	$colors['darkblue']->g = 0;
	$colors['darkblue']->b = 139;

	$colors['darkcyan'] = new stdClass ();
	$colors['darkcyan']->hex = '#008B8B';
	$colors['darkcyan']->r = 0;
	$colors['darkcyan']->g = 139;
	$colors['darkcyan']->b = 139;

	$colors['darkgoldenrod'] = new stdClass ();
	$colors['darkgoldenrod']->hex = '#B8860B';
	$colors['darkgoldenrod']->r = 184;
	$colors['darkgoldenrod']->g = 134;
	$colors['darkgoldenrod']->b = 11;

	$colors['darkgray'] = new stdClass ();
	$colors['darkgray']->hex = '#A9A9A9';
	$colors['darkgray']->r = 169;
	$colors['darkgray']->g = 169;
	$colors['darkgray']->b = 169;

	$colors['darkgreen'] = new stdClass ();
	$colors['darkgreen']->hex = '#006400';
	$colors['darkgreen']->r = 0;
	$colors['darkgreen']->g = 100;
	$colors['darkgreen']->b = 0;

	$colors['darkkhaki'] = new stdClass ();
	$colors['darkkhaki']->hex = '#BDB76B';
	$colors['darkkhaki']->r = 189;
	$colors['darkkhaki']->g = 183;
	$colors['darkkhaki']->b = 107;

	$colors['darkmagenta'] = new stdClass ();
	$colors['darkmagenta']->hex = '#8B008B';
	$colors['darkmagenta']->r = 139;
	$colors['darkmagenta']->g = 0;
	$colors['darkmagenta']->b = 139;

	$colors['darkolivegreen'] = new stdClass ();
	$colors['darkolivegreen']->hex = '#556B2F';
	$colors['darkolivegreen']->r = 85;
	$colors['darkolivegreen']->g = 107;
	$colors['darkolivegreen']->b = 47;

	$colors['darkorange'] = new stdClass ();
	$colors['darkorange']->hex = '#FF8C00';
	$colors['darkorange']->r = 255;
	$colors['darkorange']->g = 140;
	$colors['darkorange']->b = 0;

	$colors['darkorchid'] = new stdClass ();
	$colors['darkorchid']->hex = '#9932CC';
	$colors['darkorchid']->r = 153;
	$colors['darkorchid']->g = 50;
	$colors['darkorchid']->b = 204;

	$colors['darkred'] = new stdClass ();
	$colors['darkred']->hex = '#8B0000';
	$colors['darkred']->r = 139;
	$colors['darkred']->g = 0;
	$colors['darkred']->b = 0;

	$colors['darksalmon'] = new stdClass ();
	$colors['darksalmon']->hex = '#E9967A';
	$colors['darksalmon']->r = 233;
	$colors['darksalmon']->g = 150;
	$colors['darksalmon']->b = 122;

	$colors['darkseagreen'] = new stdClass ();
	$colors['darkseagreen']->hex = '#8FBC8F';
	$colors['darkseagreen']->r = 143;
	$colors['darkseagreen']->g = 188;
	$colors['darkseagreen']->b = 143;

	$colors['darkslateblue'] = new stdClass ();
	$colors['darkslateblue']->hex = '#483D8B';
	$colors['darkslateblue']->r = 72;
	$colors['darkslateblue']->g = 61;
	$colors['darkslateblue']->b = 139;

	$colors['darkslategray'] = new stdClass ();
	$colors['darkslategray']->hex = '#2F4F4F';
	$colors['darkslategray']->r = 47;
	$colors['darkslategray']->g = 79;
	$colors['darkslategray']->b = 79;

	$colors['darkturquoise'] = new stdClass ();
	$colors['darkturquoise']->hex = '#00CED1';
	$colors['darkturquoise']->r = 0;
	$colors['darkturquoise']->g = 206;
	$colors['darkturquoise']->b = 209;

	$colors['darkviolet'] = new stdClass ();
	$colors['darkviolet']->hex = '#9400D3';
	$colors['darkviolet']->r = 148;
	$colors['darkviolet']->g = 0;
	$colors['darkviolet']->b = 211;

	$colors['deeppink'] = new stdClass ();
	$colors['deeppink']->hex = '#FF1493';
	$colors['deeppink']->r = 255;
	$colors['deeppink']->g = 20;
	$colors['deeppink']->b = 147;

	$colors['deepskyblue'] = new stdClass ();
	$colors['deepskyblue']->hex = '#00BFFF';
	$colors['deepskyblue']->r = 0;
	$colors['deepskyblue']->g = 191;
	$colors['deepskyblue']->b = 255;

	$colors['dimgray'] = new stdClass ();
	$colors['dimgray']->hex = '#696969';
	$colors['dimgray']->r = 105;
	$colors['dimgray']->g = 105;
	$colors['dimgray']->b = 105;

	$colors['dodgerblue'] = new stdClass ();
	$colors['dodgerblue']->hex = '#1E90FF';
	$colors['dodgerblue']->r = 30;
	$colors['dodgerblue']->g = 144;
	$colors['dodgerblue']->b = 255;

	$colors['firebrick'] = new stdClass ();
	$colors['firebrick']->hex = '#B22222';
	$colors['firebrick']->r = 178;
	$colors['firebrick']->g = 34;
	$colors['firebrick']->b = 34;

	$colors['floralwhite'] = new stdClass ();
	$colors['floralwhite']->hex = '#FFFAF0';
	$colors['floralwhite']->r = 255;
	$colors['floralwhite']->g = 250;
	$colors['floralwhite']->b = 240;

	$colors['forestgreen'] = new stdClass ();
	$colors['forestgreen']->hex = '#228B22';
	$colors['forestgreen']->r = 34;
	$colors['forestgreen']->g = 139;
	$colors['forestgreen']->b = 34;

	$colors['fuchsia'] = new stdClass ();
	$colors['fuchsia']->hex = '#FF00FF';
	$colors['fuchsia']->r = 255;
	$colors['fuchsia']->g = 0;
	$colors['fuchsia']->b = 255;

	$colors['gainsboro'] = new stdClass ();
	$colors['gainsboro']->hex = '#DCDCDC';
	$colors['gainsboro']->r = 220;
	$colors['gainsboro']->g = 220;
	$colors['gainsboro']->b = 220;

	$colors['ghostwhite'] = new stdClass ();
	$colors['ghostwhite']->hex = '#F8F8FF';
	$colors['ghostwhite']->r = 248;
	$colors['ghostwhite']->g = 248;
	$colors['ghostwhite']->b = 255;

	$colors['gold'] = new stdClass ();
	$colors['gold']->hex = '#FFD700';
	$colors['gold']->r = 255;
	$colors['gold']->g = 215;
	$colors['gold']->b = 0;

	$colors['goldenrod'] = new stdClass ();
	$colors['goldenrod']->hex = '#DAA520';
	$colors['goldenrod']->r = 218;
	$colors['goldenrod']->g = 165;
	$colors['goldenrod']->b = 32;

	$colors['gray'] = new stdClass ();
	$colors['gray']->hex = '#808080';
	$colors['gray']->r = 128;
	$colors['gray']->g = 128;
	$colors['gray']->b = 128;

	$colors['green'] = new stdClass ();
	$colors['green']->hex = '#008000';
	$colors['green']->r = 0;
	$colors['green']->g = 128;
	$colors['green']->b = 0;

	$colors['greenyellow'] = new stdClass ();
	$colors['greenyellow']->hex = '#ADFF2F';
	$colors['greenyellow']->r = 173;
	$colors['greenyellow']->g = 255;
	$colors['greenyellow']->b = 47;

	$colors['honeydew'] = new stdClass ();
	$colors['honeydew']->hex = '#F0FFF0';
	$colors['honeydew']->r = 240;
	$colors['honeydew']->g = 255;
	$colors['honeydew']->b = 240;

	$colors['hotpink'] = new stdClass ();
	$colors['hotpink']->hex = '#FF69B4';
	$colors['hotpink']->r = 255;
	$colors['hotpink']->g = 105;
	$colors['hotpink']->b = 180;

	$colors['indianred'] = new stdClass ();
	$colors['indianred']->hex = '#CD5C5C';
	$colors['indianred']->r = 205;
	$colors['indianred']->g = 92;
	$colors['indianred']->b = 92;

	$colors['indigo'] = new stdClass ();
	$colors['indigo']->hex = '#4B0082';
	$colors['indigo']->r = 75;
	$colors['indigo']->g = 0;
	$colors['indigo']->b = 130;

	$colors['ivory'] = new stdClass ();
	$colors['ivory']->hex = '#FFFFF0';
	$colors['ivory']->r = 255;
	$colors['ivory']->g = 255;
	$colors['ivory']->b = 240;

	$colors['khaki'] = new stdClass ();
	$colors['khaki']->hex = '#F0E68C';
	$colors['khaki']->r = 240;
	$colors['khaki']->g = 230;
	$colors['khaki']->b = 140;

	$colors['lavender'] = new stdClass ();
	$colors['lavender']->hex = '#E6E6FA';
	$colors['lavender']->r = 230;
	$colors['lavender']->g = 230;
	$colors['lavender']->b = 250;

	$colors['lavenderblush'] = new stdClass ();
	$colors['lavenderblush']->hex = '#FFF0F5';
	$colors['lavenderblush']->r = 255;
	$colors['lavenderblush']->g = 240;
	$colors['lavenderblush']->b = 245;

	$colors['lawngreen'] = new stdClass ();
	$colors['lawngreen']->hex = '#7CFC00';
	$colors['lawngreen']->r = 124;
	$colors['lawngreen']->g = 252;
	$colors['lawngreen']->b = 0;

	$colors['lemonchiffon'] = new stdClass ();
	$colors['lemonchiffon']->hex = '#FFFACD';
	$colors['lemonchiffon']->r = 255;
	$colors['lemonchiffon']->g = 250;
	$colors['lemonchiffon']->b = 205;

	$colors['lightblue'] = new stdClass ();
	$colors['lightblue']->hex = '#ADD8E6';
	$colors['lightblue']->r = 173;
	$colors['lightblue']->g = 216;
	$colors['lightblue']->b = 230;

	$colors['lightcoral'] = new stdClass ();
	$colors['lightcoral']->hex = '#F08080';
	$colors['lightcoral']->r = 240;
	$colors['lightcoral']->g = 128;
	$colors['lightcoral']->b = 128;

	$colors['lightcyan'] = new stdClass ();
	$colors['lightcyan']->hex = '#E0FFFF';
	$colors['lightcyan']->r = 224;
	$colors['lightcyan']->g = 255;
	$colors['lightcyan']->b = 255;

	$colors['lightgoldenrodyellow'] = new stdClass ();
	$colors['lightgoldenrodyellow']->hex = '#FAFAD2';
	$colors['lightgoldenrodyellow']->r = 250;
	$colors['lightgoldenrodyellow']->g = 250;
	$colors['lightgoldenrodyellow']->b = 210;

	$colors['lightgreen'] = new stdClass ();
	$colors['lightgreen']->hex = '#90EE90';
	$colors['lightgreen']->r = 144;
	$colors['lightgreen']->g = 238;
	$colors['lightgreen']->b = 144;

	$colors['lightgrey'] = new stdClass ();
	$colors['lightgrey']->hex = '#D3D3D3';
	$colors['lightgrey']->r = 211;
	$colors['lightgrey']->g = 211;
	$colors['lightgrey']->b = 211;

	$colors['lightpink'] = new stdClass ();
	$colors['lightpink']->hex = '#FFB6C1';
	$colors['lightpink']->r = 255;
	$colors['lightpink']->g = 182;
	$colors['lightpink']->b = 193;

	$colors['lightsalmon'] = new stdClass ();
	$colors['lightsalmon']->hex = '#FFA07A';
	$colors['lightsalmon']->r = 255;
	$colors['lightsalmon']->g = 160;
	$colors['lightsalmon']->b = 122;

	$colors['lightseagreen'] = new stdClass ();
	$colors['lightseagreen']->hex = '#20B2AA';
	$colors['lightseagreen']->r = 32;
	$colors['lightseagreen']->g = 178;
	$colors['lightseagreen']->b = 170;

	$colors['lightskyblue'] = new stdClass ();
	$colors['lightskyblue']->hex = '#87CEFA';
	$colors['lightskyblue']->r = 135;
	$colors['lightskyblue']->g = 206;
	$colors['lightskyblue']->b = 250;

	$colors['lightslateblue'] = new stdClass ();
	$colors['lightslateblue']->hex = '#8470FF';
	$colors['lightslateblue']->r = 132;
	$colors['lightslateblue']->g = 112;
	$colors['lightslateblue']->b = 255;

	$colors['lightslategray'] = new stdClass ();
	$colors['lightslategray']->hex = '#778899';
	$colors['lightslategray']->r = 119;
	$colors['lightslategray']->g = 136;
	$colors['lightslategray']->b = 153;

	$colors['lightsteelblue'] = new stdClass ();
	$colors['lightsteelblue']->hex = '#B0C4DE';
	$colors['lightsteelblue']->r = 176;
	$colors['lightsteelblue']->g = 196;
	$colors['lightsteelblue']->b = 222;

	$colors['lightyellow'] = new stdClass ();
	$colors['lightyellow']->hex = '#FFFFE0';
	$colors['lightyellow']->r = 255;
	$colors['lightyellow']->g = 255;
	$colors['lightyellow']->b = 224;

	$colors['lime'] = new stdClass ();
	$colors['lime']->hex = '#00FF00';
	$colors['lime']->r = 0;
	$colors['lime']->g = 255;
	$colors['lime']->b = 0;

	$colors['limegreen'] = new stdClass ();
	$colors['limegreen']->hex = '#32CD32';
	$colors['limegreen']->r = 50;
	$colors['limegreen']->g = 205;
	$colors['limegreen']->b = 50;

	$colors['linen'] = new stdClass ();
	$colors['linen']->hex = '#FAF0E6';
	$colors['linen']->r = 250;
	$colors['linen']->g = 240;
	$colors['linen']->b = 230;

	$colors['magenta'] = new stdClass ();
	$colors['magenta']->hex = '#FF00FF';
	$colors['magenta']->r = 255;
	$colors['magenta']->g = 0;
	$colors['magenta']->b = 255;

	$colors['maroon'] = new stdClass ();
	$colors['maroon']->hex = '#800000';
	$colors['maroon']->r = 128;
	$colors['maroon']->g = 0;
	$colors['maroon']->b = 0;

	$colors['mediumaquamarine'] = new stdClass ();
	$colors['mediumaquamarine']->hex = '#66CDAA';
	$colors['mediumaquamarine']->r = 102;
	$colors['mediumaquamarine']->g = 205;
	$colors['mediumaquamarine']->b = 170;

	$colors['mediumblue'] = new stdClass ();
	$colors['mediumblue']->hex = '#0000CD';
	$colors['mediumblue']->r = 0;
	$colors['mediumblue']->g = 0;
	$colors['mediumblue']->b = 205;

	$colors['mediumorchid'] = new stdClass ();
	$colors['mediumorchid']->hex = '#BA55D3';
	$colors['mediumorchid']->r = 186;
	$colors['mediumorchid']->g = 85;
	$colors['mediumorchid']->b = 211;

	$colors['mediumpurple'] = new stdClass ();
	$colors['mediumpurple']->hex = '#9370D8';
	$colors['mediumpurple']->r = 147;
	$colors['mediumpurple']->g = 112;
	$colors['mediumpurple']->b = 219;

	$colors['mediumseagreen'] = new stdClass ();
	$colors['mediumseagreen']->hex = '#3CB371';
	$colors['mediumseagreen']->r = 60;
	$colors['mediumseagreen']->g = 179;
	$colors['mediumseagreen']->b = 113;

	$colors['mediumslateblue'] = new stdClass ();
	$colors['mediumslateblue']->hex = '#7B68EE';
	$colors['mediumslateblue']->r = 123;
	$colors['mediumslateblue']->g = 104;
	$colors['mediumslateblue']->b = 238;

	$colors['mediumspringgreen'] = new stdClass ();
	$colors['mediumspringgreen']->hex = '#00FA9A';
	$colors['mediumspringgreen']->r = 0;
	$colors['mediumspringgreen']->g = 250;
	$colors['mediumspringgreen']->b = 154;

	$colors['mediumturquoise'] = new stdClass ();
	$colors['mediumturquoise']->hex = '#48D1CC';
	$colors['mediumturquoise']->r = 72;
	$colors['mediumturquoise']->g = 209;
	$colors['mediumturquoise']->b = 204;

	$colors['mediumvioletred'] = new stdClass ();
	$colors['mediumvioletred']->hex = '#C71585';
	$colors['mediumvioletred']->r = 199;
	$colors['mediumvioletred']->g = 21;
	$colors['mediumvioletred']->b = 133;

	$colors['midnightblue'] = new stdClass ();
	$colors['midnightblue']->hex = '#191970';
	$colors['midnightblue']->r = 25;
	$colors['midnightblue']->g = 25;
	$colors['midnightblue']->b = 112;

	$colors['mintcream'] = new stdClass ();
	$colors['mintcream']->hex = '#F5FFFA';
	$colors['mintcream']->r = 245;
	$colors['mintcream']->g = 255;
	$colors['mintcream']->b = 250;

	$colors['mistyrose'] = new stdClass ();
	$colors['mistyrose']->hex = '#FFE4E1';
	$colors['mistyrose']->r = 255;
	$colors['mistyrose']->g = 228;
	$colors['mistyrose']->b = 225;

	$colors['moccasin'] = new stdClass ();
	$colors['moccasin']->hex = '#FFE4B5';
	$colors['moccasin']->r = 255;
	$colors['moccasin']->g = 228;
	$colors['moccasin']->b = 181;

	$colors['navajowhite'] = new stdClass ();
	$colors['navajowhite']->hex = '#FFDEAD';
	$colors['navajowhite']->r = 255;
	$colors['navajowhite']->g = 222;
	$colors['navajowhite']->b = 173;

	$colors['navy'] = new stdClass ();
	$colors['navy']->hex = '#000080';
	$colors['navy']->r = 0;
	$colors['navy']->g = 0;
	$colors['navy']->b = 128;

	$colors['oldlace'] = new stdClass ();
	$colors['oldlace']->hex = '#FDF5E6';
	$colors['oldlace']->r = 253;
	$colors['oldlace']->g = 245;
	$colors['oldlace']->b = 230;

	$colors['olive'] = new stdClass ();
	$colors['olive']->hex = '#808000';
	$colors['olive']->r = 128;
	$colors['olive']->g = 128;
	$colors['olive']->b = 0;

	$colors['olivedrab'] = new stdClass ();
	$colors['olivedrab']->hex = '#6B8E23';
	$colors['olivedrab']->r = 107;
	$colors['olivedrab']->g = 142;
	$colors['olivedrab']->b = 35;

	$colors['orange'] = new stdClass ();
	$colors['orange']->hex = '#FFA500';
	$colors['orange']->r = 255;
	$colors['orange']->g = 165;
	$colors['orange']->b = 0;

	$colors['orangered'] = new stdClass ();
	$colors['orangered']->hex = '#FF4500';
	$colors['orangered']->r = 255;
	$colors['orangered']->g = 69;
	$colors['orangered']->b = 0;

	$colors['orchid'] = new stdClass ();
	$colors['orchid']->hex = '#DA70D6';
	$colors['orchid']->r = 218;
	$colors['orchid']->g = 112;
	$colors['orchid']->b = 214;

	$colors['palegoldenrod'] = new stdClass ();
	$colors['palegoldenrod']->hex = '#EEE8AA';
	$colors['palegoldenrod']->r = 238;
	$colors['palegoldenrod']->g = 232;
	$colors['palegoldenrod']->b = 170;

	$colors['palegreen'] = new stdClass ();
	$colors['palegreen']->hex = '#98FB98';
	$colors['palegreen']->r = 152;
	$colors['palegreen']->g = 251;
	$colors['palegreen']->b = 152;

	$colors['paleturquoise'] = new stdClass ();
	$colors['paleturquoise']->hex = '#AFEEEE';
	$colors['paleturquoise']->r = 175;
	$colors['paleturquoise']->g = 238;
	$colors['paleturquoise']->b = 238;

	$colors['palevioletred'] = new stdClass ();
	$colors['palevioletred']->hex = '#DB7093';
	$colors['palevioletred']->r = 219;
	$colors['palevioletred']->g = 112;
	$colors['palevioletred']->b = 147;

	$colors['papayawhip'] = new stdClass ();
	$colors['papayawhip']->hex = '#FFEFD5';
	$colors['papayawhip']->r = 255;
	$colors['papayawhip']->g = 239;
	$colors['papayawhip']->b = 213;

	$colors['peachpuff'] = new stdClass ();
	$colors['peachpuff']->hex = '#FFDA89';
	$colors['peachpuff']->r = 255;
	$colors['peachpuff']->g = 218;
	$colors['peachpuff']->b = 185;

	$colors['peru'] = new stdClass ();
	$colors['peru']->hex = '#CD853F';
	$colors['peru']->r = 205;
	$colors['peru']->g = 133;
	$colors['peru']->b = 63;

	$colors['pink'] = new stdClass ();
	$colors['pink']->hex = '#FFC0CB';
	$colors['pink']->r = 255;
	$colors['pink']->g = 192;
	$colors['pink']->b = 203;

	$colors['plum'] = new stdClass ();
	$colors['plum']->hex = '#DDA0DD';
	$colors['plum']->r = 221;
	$colors['plum']->g = 160;
	$colors['plum']->b = 221;

	$colors['powderblue'] = new stdClass ();
	$colors['powderblue']->hex = '#B0E0E6';
	$colors['powderblue']->r = 176;
	$colors['powderblue']->g = 224;
	$colors['powderblue']->b = 230;

	$colors['purple'] = new stdClass ();
	$colors['purple']->hex = '#800080';
	$colors['purple']->r = 128;
	$colors['purple']->g = 0;
	$colors['purple']->b = 128;

	$colors['red'] = new stdClass ();
	$colors['red']->hex = '#FF0000';
	$colors['red']->r = 255;
	$colors['red']->g = 0;
	$colors['red']->b = 0;

	$colors['rosybrown'] = new stdClass ();
	$colors['rosybrown']->hex = '#BC8F8F';
	$colors['rosybrown']->r = 188;
	$colors['rosybrown']->g = 143;
	$colors['rosybrown']->b = 143;

	$colors['royalblue'] = new stdClass ();
	$colors['royalblue']->hex = '#4169E1';
	$colors['royalblue']->r = 65;
	$colors['royalblue']->g = 105;
	$colors['royalblue']->b = 225;

	$colors['saddlebrown'] = new stdClass ();
	$colors['saddlebrown']->hex = '#8B4513';
	$colors['saddlebrown']->r = 139;
	$colors['saddlebrown']->g = 69;
	$colors['saddlebrown']->b = 19;

	$colors['salmon'] = new stdClass ();
	$colors['salmon']->hex = '#FA8072';
	$colors['salmon']->r = 250;
	$colors['salmon']->g = 128;
	$colors['salmon']->b = 114;

	$colors['sandybrown'] = new stdClass ();
	$colors['sandybrown']->hex = '#F4A460';
	$colors['sandybrown']->r = 244;
	$colors['sandybrown']->g = 164;
	$colors['sandybrown']->b = 96;

	$colors['seagreen'] = new stdClass ();
	$colors['seagreen']->hex = '#2E8B57';
	$colors['seagreen']->r = 46;
	$colors['seagreen']->g = 139;
	$colors['seagreen']->b = 87;

	$colors['seashell'] = new stdClass ();
	$colors['seashell']->hex = '#FFF5EE';
	$colors['seashell']->r = 255;
	$colors['seashell']->g = 245;
	$colors['seashell']->b = 238;

	$colors['sienna'] = new stdClass ();
	$colors['sienna']->hex = '#A0522D';
	$colors['sienna']->r = 160;
	$colors['sienna']->g = 82;
	$colors['sienna']->b = 45;

	$colors['silver'] = new stdClass ();
	$colors['silver']->hex = '#C0C0C0';
	$colors['silver']->r = 192;
	$colors['silver']->g = 192;
	$colors['silver']->b = 192;

	$colors['skyblue'] = new stdClass ();
	$colors['skyblue']->hex = '#87CEEB';
	$colors['skyblue']->r = 135;
	$colors['skyblue']->g = 206;
	$colors['skyblue']->b = 235;

	$colors['slateblue'] = new stdClass ();
	$colors['slateblue']->hex = '#6A5ACD';
	$colors['slateblue']->r = 106;
	$colors['slateblue']->g = 90;
	$colors['slateblue']->b = 205;

	$colors['slategray'] = new stdClass ();
	$colors['slategray']->hex = '#708090';
	$colors['slategray']->r = 112;
	$colors['slategray']->g = 128;
	$colors['slategray']->b = 144;

	$colors['snow'] = new stdClass ();
	$colors['snow']->hex = '#FFFAFA';
	$colors['snow']->r = 255;
	$colors['snow']->g = 250;
	$colors['snow']->b = 250;

	$colors['springgreen'] = new stdClass ();
	$colors['springgreen']->hex = '#00FF7F';
	$colors['springgreen']->r = 0;
	$colors['springgreen']->g = 255;
	$colors['springgreen']->b = 127;

	$colors['steelblue'] = new stdClass ();
	$colors['steelblue']->hex = '#4682B4';
	$colors['steelblue']->r = 70;
	$colors['steelblue']->g = 130;
	$colors['steelblue']->b = 180;

	$colors['tan'] = new stdClass ();
	$colors['tan']->hex = '#D2B48C';
	$colors['tan']->r = 210;
	$colors['tan']->g = 180;
	$colors['tan']->b = 140;

	$colors['teal'] = new stdClass ();
	$colors['teal']->hex = '#008080';
	$colors['teal']->r = 0;
	$colors['teal']->g = 128;
	$colors['teal']->b = 128;

	$colors['thistle'] = new stdClass ();
	$colors['thistle']->hex = '#D8BFD8';
	$colors['thistle']->r = 216;
	$colors['thistle']->g = 191;
	$colors['thistle']->b = 216;

	$colors['tomato'] = new stdClass ();
	$colors['tomato']->hex = '#FF6347';
	$colors['tomato']->r = 255;
	$colors['tomato']->g = 99;
	$colors['tomato']->b = 71;

	$colors['turquoise'] = new stdClass ();
	$colors['turquoise']->hex = '#40E0D0';
	$colors['turquoise']->r = 64;
	$colors['turquoise']->g = 224;
	$colors['turquoise']->b = 208;

	$colors['violet'] = new stdClass ();
	$colors['violet']->hex = '#EE82EE';
	$colors['violet']->r = 238;
	$colors['violet']->g = 130;
	$colors['violet']->b = 238;

	$colors['violetred'] = new stdClass ();
	$colors['violetred']->hex = '#D02090';
	$colors['violetred']->r = 208;
	$colors['violetred']->g = 32;
	$colors['violetred']->b = 144;

	$colors['wheat'] = new stdClass ();
	$colors['wheat']->hex = '#F5DEB3';
	$colors['wheat']->r = 245;
	$colors['wheat']->g = 222;
	$colors['wheat']->b = 179;

	$colors['white'] = new stdClass ();
	$colors['white']->hex = '#FFFFFF';
	$colors['white']->r = 255;
	$colors['white']->g = 255;
	$colors['white']->b = 255;

	$colors['whitesmoke'] = new stdClass ();
	$colors['whitesmoke']->hex = '#F5F5F5';
	$colors['whitesmoke']->r = 245;
	$colors['whitesmoke']->g = 245;
	$colors['whitesmoke']->b = 245;

	$colors['yellow'] = new stdClass ();
	$colors['yellow']->hex = '#FFFF00';
	$colors['yellow']->r = 255;
	$colors['yellow']->g = 255;
	$colors['yellow']->b = 0;

	$colors['yellowgreen'] = new stdClass ();
	$colors['yellowgreen']->hex = '#9ACD32';
	$colors['yellowgreen']->r = 154;
	$colors['yellowgreen']->g = 205;
	$colors['yellowgreen']->b = 50;

	$color_name = strtolower (str_replace (array (" ", "_", "-"), "", $color_name));

	if (isset ($colors[$color_name]))
	{
		$c = $colors[$color_name];

		// flag used in case image object has been passed
		$use_delta = $delta ? true : false;

		// create the hilights and shadows
		$delta = $delta ? intval ($delta) : 36;

		$c->hr = $c->r + $delta;
		$c->hr = $c->hr < 0 ? 0 : $c->hr;
		$c->hr = $c->hr > 255 ? 255 : $c->hr;

		$c->hg = $c->g + $delta;
		$c->hg = $c->hg < 0 ? 0 : $c->hg;
		$c->hg = $c->hg > 255 ? 255 : $c->hg;

		$c->hb = $c->b + $delta;
		$c->hb = $c->hb < 0 ? 0 : $c->hb;
		$c->hb = $c->hb > 255 ? 255 : $c->hb;

		$c->hhex  = "#".str_pad (dechex ($c->hr), 2, "0", STR_PAD_LEFT);
		$c->hhex .= str_pad (dechex ($c->hg), 2, "0", STR_PAD_LEFT);
		$c->hhex .= str_pad (dechex ($c->hb), 2, "0", STR_PAD_LEFT);

		$c->sr = $c->r - $delta;
		$c->sr = $c->sr < 0 ? 0 : $c->sr;
		$c->sr = $c->sr > 255 ? 255 : $c->sr;

		$c->sg = $c->g - $delta;
		$c->sg = $c->sg < 0 ? 0 : $c->sg;
		$c->sg = $c->sg > 255 ? 255 : $c->sg;

		$c->sb = $c->b - $delta;
		$c->sb = $c->sb < 0 ? 0 : $c->sb;
		$c->sb = $c->sb > 255 ? 255 : $c->sb;

		$c->shex  = "#".str_pad (dechex ($c->sr), 2, "0", STR_PAD_LEFT);
		$c->shex .= str_pad (dechex ($c->sg), 2, "0", STR_PAD_LEFT);
		$c->shex .= str_pad (dechex ($c->sb), 2, "0", STR_PAD_LEFT);

		if ($image_obj)
		{
			if (is_resource ($image_obj))
			{
				if ($use_delta)
				{
					// we use hilight color only; to darken a color a
					// negative number should be used as delta.
					// $image_obj should be the only context in which
					// delta is negative.
					$c->r = $c->hr;
					$c->g = $c->hg;
					$c->b = $c->hb;
				}
				if ($image_alpha)
				{
					$image_alpha = intval ($image_alpha);
					$image_alpha = $image_alpha < 0 ? 0 : $image_alpha;
					$image_alpha = $image_alpha > 127 ? 127 : $image_alpha;
					return imagecolorallocatealpha ($image_obj, $c->r, $c->g, $c->b, $image_alpha);
				}
				else
				{
					return imagecolorallocate ($image_obj, $c->r, $c->g, $c->b);
				}
			}
			else
			{
				return false;
			}
		}
		else
		{
			return $c;
		}
	}
	elseif ($color_name = "list")
	{
		$output_str = "";
		foreach ($colors as $name=>$c)
		{
			$delta = $delat ? intval ($delta) : 36;

			$c->hr = $c->r + $delta;
			$c->hr = $c->hr < 0 ? 0 : $c->hr;
			$c->hr = $c->hr > 255 ? 255 : $c->hr;

			$c->hg = $c->g + $delta;
			$c->hg = $c->hg < 0 ? 0 : $c->hg;
			$c->hg = $c->hg > 255 ? 255 : $c->hg;

			$c->hb = $c->b + $delta;
			$c->hb = $c->hb < 0 ? 0 : $c->hb;
			$c->hb = $c->hb > 255 ? 255 : $c->hb;

			$c->hhex  = "#".str_pad (dechex ($c->hr), 2, "0", STR_PAD_LEFT);
			$c->hhex .= str_pad (dechex ($c->hg), 2, "0", STR_PAD_LEFT);
			$c->hhex .= str_pad (dechex ($c->hb), 2, "0", STR_PAD_LEFT);

			$c->sr = $c->r - $delta;
			$c->sr = $c->sr < 0 ? 0 : $c->sr;
			$c->sr = $c->sr > 255 ? 255 : $c->sr;

			$c->sg = $c->g - $delta;
			$c->sg = $c->sg < 0 ? 0 : $c->sg;
			$c->sg = $c->sg > 255 ? 255 : $c->sg;

			$c->sb = $c->b - $delta;
			$c->sb = $c->sb < 0 ? 0 : $c->sb;
			$c->sb = $c->sb > 255 ? 255 : $c->sb;

			$c->shex  = "#".str_pad (dechex ($c->sr), 2, "0", STR_PAD_LEFT);
			$c->shex .= str_pad (dechex ($c->sg), 2, "0", STR_PAD_LEFT);
			$c->shex .= str_pad (dechex ($c->sb), 2, "0", STR_PAD_LEFT);
			$output_str .= "<div style=\"position:relative;margin:4px;\"><span style=\"border:solid 1px black;border-right:0px;background-color:{$c->shex};\">&nbsp;&nbsp;&nbsp;&nbsp;</span><span style=\"border:solid 1px black;background-color:{$c->hex};\">&nbsp;&nbsp;&nbsp;&nbsp;</span><span style=\"border:solid 1px black;border-left:0px;background-color:{$c->hhex};\">&nbsp;&nbsp;&nbsp;&nbsp;</span> {$name} </div>\n";
		}
		return $output_str;
	}
	else
	{
		return false;
	}
}
?>