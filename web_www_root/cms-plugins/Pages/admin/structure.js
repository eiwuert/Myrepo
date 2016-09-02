$(function()
{
	$('ol.sortable').nestedSortable({
		disableNesting: 'no-nest',
		forcePlaceholderSize: true,
		handle: 'div',
		helper:	'clone',
		items: 'li:not(.unmovable)',
		maxLevels: 25,
		opacity: .6,
		placeholder: 'placeholder',
		revert: 250,
		tabSize: 100,
		tolerance: 'pointer',
		toleranceElement: '> div',
		connectWith: ".connectedSortable",
		stop: function(){
		   colorLevels($('#structure'), -1);
		   $("#not_on_menu").find('li').addClass('no-nest');
		   $("#structure").find('li').not('.unmovable').removeClass('no-nest');
		  setupNotOnMenu();
		}
	});
	colorLevels($('#structure'), -1);
	$("#not_on_menu").find('li').addClass('no-nest');
	$("#structure").find('li').not('.unmovable').removeClass('no-nest');
	setupNotOnMenu();
	setupSave();

});

function setupSave()
{
	$('.save_structure').click(function(){
		on_menu = $('#structure').nestedSortable('toArray', {startDepthCount: 0});
		not_on_menu = $('#not_on_menu').nestedSortable('toArray', {startDepthCount: 0});
		jRpc.send(lum_handleResponse, {plugin: 'Pages', method: 'saveStructure', params: {on_menu: on_menu, not_on_menu: not_on_menu}});
	});
}

function setupNotOnMenu()
{
	// un-nest the items
	$("#not_on_menu").find('li').appendTo($("#not_on_menu").first('ol'));
	
	$("#not_on_menu").find('div').css('background', '#FFF7AA');
	$("#not_on_menu").find('div').css('borderColor', '#E3CA4B');
}


function colorLevels(obj, level)
{
    level++;
    $(obj).children('li').each(function(){
	$(this).children('div').each(function(){
	    var colors = getLevelColors(level)
	   $(this).css('background', colors[0]);
	   $(this).css('borderColor', colors[1]);
	});	
	$(this).children('ol').each(function(){
	    colorLevels($(this), level);
	});
    });
}

// this function gets a color from the spectrum
// based on a sin wave
function getLevelColors(level)
{
    phase = 13 + (level * 5); // start at light blue
    
    // for pastel colors
    center = 210;
    width = 45;
    
    // separate frequency for each color
    rf = .1;
    bf = .2;
    gf = .3;
    
    // determine color
    red   = Math.sin(rf*phase+2+phase) * width + center;
    green = Math.sin(bf*phase+0+phase) * width + center;
    blue  = Math.sin(gf*phase+4+phase) * width + center;
    
    // and make it a little brighter
    red = (red + 10 > 255 ? 255 : red + 10);
    green = (green + 10 > 255 ? 255 : green + 10);
    blue = (blue + 10 > 255 ? 255 : blue + 10);
    var background = RGB2Color(red,green,blue);
    
    // now darken the color fro the border
    red = (red - 30 < 0 ? 0 : red - 30);
    green = (green - 30 < 0 ? 0 : green - 30);
    blue = (blue - 30 < 0 ? 0 : blue - 30);
    var border = RGB2Color(red,green,blue);
    
    return [background, border];

}

function RGB2Color(r,g,b)
{
  return '#' + byte2Hex(r) + byte2Hex(g) + byte2Hex(b);
}

function byte2Hex(n)
{
  var nybHexString = "0123456789ABCDEF";
  return String(nybHexString.substr((n >> 4) & 0x0F,1)) + nybHexString.substr(n & 0x0F,1);
}
