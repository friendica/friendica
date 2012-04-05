
IMAGES=add.png edit.png	gear.png info.png menu.png \
		notify_off.png star.png  delete.png feed.png group.png \
		lock.png notice.png notify_on.png user.png link.png \
		play.png plugin.png unlock.png zip.png audio.png video.png \
		image.png text.png

DESTS=10/ 16/ 22/ 48/ \
		$(addprefix 10/, $(IMAGES)) \
		$(addprefix 16/, $(IMAGES)) \
		$(addprefix 22/, $(IMAGES)) \
		$(addprefix 48/, $(IMAGES))
	 
all: $(DESTS)

%/:
	mkdir $@

10/%.png: %.png
	convert $< -resize 10x10 $@
	
16/%.png: %.png
	convert $< -resize 16x16 $@

22/%.png: %.png
	convert $< -resize 22x22 $@

48/%.png: %.png
	convert $< -resize 48x48 $@




