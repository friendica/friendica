/**
 * @brief Javascript for the display module
 */

// Catch the GUID from the URL
var itemGuid = window.location.pathname.split("/").pop();
var itemGuidSafe = itemGuid.replace(/%.*/, '');

$(window).load(function(){
	// Scroll to the Item by its GUID
	scrollToItem('item-' + itemGuidSafe);
});
