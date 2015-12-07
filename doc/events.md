# Events

* [Home](help)

A special form of postings are events.
The events you and your contacts share can be found at [/events](/events) on your node.
To get there go to your wall and follow the tab "events"
Depending on the theme you are using, there might be an additional link from the navigation menu to this page.

## Event Overview

The overview page shows the calendar of the current month, plus eventually some days in the beginning and the end.
Listed are all events for this month you created, or your contacts have shared with you.
This includes birthday reminders for contacts who share their birthday with you.

From the controls, you can switch between month/week/day view.
Flip through the view forwards and backwards.
And return to *today*.

To create a new event, you can either follow the link "Create New Event" or make a double click on the desired box in the calendarium for when the event should take place.

With a click on an existing event a pop-up box will be opened which shows you the event.
From there you can edit the event or view the event at the source link, if you are the one who created the event.

## Create a new Event

Following one of the methods mentioned above you reach a form to enter the event data.
Fields marked with a *** have to be filled.

* **Event Starts**: enter the date/time of the start of the event here
* **Event Finishes**: enter the finishing date/time for the event here

When you click in one of these fields a pop-up will be opened that allows you to pick the day and the time.
If you double clicked on the day box in the calendarium these fields will be pre-filled for you.
The finishing date/time has to be after the beginning date/time of the event.
But you don't have to specify it.
If the event is open-end or the finishing date/time does not matter, just select the box below the two first fields.

* **Adjust for viewer timezone**: If you check this box, the beginning and finisching times will automatically converted to the local time according to the timezone setting

This might prevent too early birthday wishes, or the panic attac that you have forgotten the birthday from your buddy at the other end of the world.
And similar events.

* **Title**: a title for the event
* **Description**: a longer description for the event
* **Location**: the location the event will took place

These three fields describe your events.
In the descirption and location field you can use BBCode to format the text.

* **Share this event**: when this box is checked the ACL will be shown to let you select with whom you wish to share the event. This works just like the controls of any other posting.

When you *Share* the event it will be posted to your wall with the access permissions you've selected.
But before you do, you can also *preview* the event in a pop-up box.

### Interaction with Events

When you publish an event, you can choose who shall receive it, as with a regular new posting.
The recipients will see the posting about the event in their network-stream.
Additionally it will be added to their calendar and thus be shown in their events overview page.

Recipients of the event-posting can comment or dis-/like the event, as with a regular posting, but also announce that they will attend, not attend or may-be attend the event with a single click.

### Addons

#### OpenStreetMap

If this addon is activated on you friendica node, the content of the location field will be mathced with the identification service of OSM when you submit the event.
Should OSM find anything matching, a map for the location will be embedded automatically at the end of the events view.

#### Calendar Export

If this addon is activated the public events you have created will be published in ical or csv file. The URL of the published file is ``example.com/cal/nickname/export/format`` (where format is either ical of csv).