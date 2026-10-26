# FG Emoji Picker - Emoji picker created with vanilla javascript
This is the simplest to use emoji picker built with vanilla javascript.

![](./screenshot.png "Vanilla Javascript Emoji Picker")

## Benefits:

- It is only one .js file without css or other files
- There is no jQuery or other libraries
- Simplicity of usage
- Multiple textareas and triggers
- Draggable emoji picker container

## Initialize

Initialze plugin with ```new EmojiPicker({});```

## Options

- Trigger - must be an array of objects. Inside object there are two properties. First is selector, and second - **insertInto** method to define where emoji going to be inserted. If there are multiple 'textarea's - you can provide array of selectors as well. Watch example below.
- Close button - **closeButton** method can be true of false depending on whether you want close button on emoji picker or not.
- specialButtons - takes color code to change special (move and close) button colors.

```
new EmojiPicker({
    trigger: [
        {
            selector: '.first-btn',
            insertInto: ['.one', '.two'] // If there is only one '.selector', than it can be used without array
        },
        {
            selector: '.second-btn',
            insertInto: '.two'
        }
    ],
    closeButton: true,
    specialButtons: 'green' // #008000, rgba(0, 128, 0);
});
```

## Minified file

Only `vanillaEmojiPicker.min.js` is loaded by Friendica.
After every change to `vanillaEmojiPicker.js` it has to be regenerated, for example with [Terser](https://terser.org/):

```
npx terser@5 vanillaEmojiPicker.js -c -m -o vanillaEmojiPicker.min.js
```

## Emoji data

Emojis of recent Unicode versions carry a `"v"` property with the Unicode emoji version (for example `"v": 17`).
These emojis are only shown when the browser is able to draw them.
New emojis can be marked this way using the version column of [emoji-test.txt](https://unicode.org/Public/emoji/latest/emoji-test.txt).
