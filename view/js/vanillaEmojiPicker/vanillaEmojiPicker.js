const EmojiPicker = function(options) {

    this.options = options;
    this.trigger = this.options.trigger.map(item => item.selector);
    this.insertInto = undefined;
    let emojiesHTML = '';
    let categoriesHTML = '';
    let emojiList = undefined;
    let moseMove = false;
    let activeInsertTarget = null;
    const pickerWidth = this.options.closeButton ? 370 : 350;
    const pickerHeight = 400;

    this.lib = function(el = undefined) {

        const isNodeList = (nodes) => {
            var stringRepr = Object.prototype.toString.call(nodes);
        
            return typeof nodes === 'object' &&
                /^\[object (HTMLCollection|NodeList|Object)\]$/.test(stringRepr) &&
                (typeof nodes.length === 'number') &&
                (nodes.length === 0 || (typeof nodes[0] === "object" && nodes[0].nodeType > 0));
        }

        return {

            el: () => {
                // Check if is node
                if (!el) {
                    return undefined;
                } else if (el.nodeName) {
                    return [el];
                } else if (isNodeList(el)) {
                    return Array.from(el)
                } else if (typeof(el) === 'string' || typeof(el) === 'STRING') {
                    return Array.from(document.querySelectorAll(el));
                } else {
                    return undefined;
                }
            },

            on(event, callback, classList = undefined) {
                if (!classList) {
                    this.el().forEach(item => {
                        item.addEventListener(event, callback.bind(item))
                    })
                } else {
                    this.el().forEach(item => {
                        item.addEventListener(event, (e) => {
                            if (e.target.closest(classList)) {

                                let attr = undefined;

                                if (Array.isArray(classList)) {
                                    const stringifiedElem = e.target.outerHTML;

                                    const index = classList.findIndex(attr => stringifiedElem.includes(attr.slice(1)));

                                    attr = classList[index];
                                }

                                callback(e, attr)
                            }
                        })
                    })
                }
            },

            css(params) {
                for (const key in params) {
                    if (Object.hasOwnProperty.call(params, key)) {
                        const cssVal = params[key];
                        this.el().forEach(el => el.style[key] = cssVal)
                    }
                }
            },

            attr(param1, param2 = undefined) {

                if (!param2) {
                    return this.el()[0].getAttribute(param1)
                }
                this.el().forEach(el => el.setAttribute(param1, param2))
            },

            removeAttr(param) {
                this.el().forEach(el => el.removeAttribute(param))
            },
            
            addClass(param) {
                this.el().forEach(el => el.classList.add(param))
            },

            removeClass(param) {
                this.el().forEach(el => el.classList.remove(param))
            },
            
            slug(str) {
                return str
                    .toLowerCase()
                    .replace(/[^\u00BF-\u1FFF\u2C00-\uD7FF\w]+|[\_]+/ig, '-')
                    .replace(/ +/g,'-')
                    ;
            },

            remove(param) {
                this.el().forEach(el => el.remove())
            },

            val(param = undefined) {
                let val;

                if (param === undefined) {
                    this.el().forEach(el => {
                        val = el.value;
                    })
                } else {
                    this.el().forEach(el => {
                        el.value = param;
                    })
                }

                return val;
            },

            text(msg = undefined) {
                if (msg === undefined) {
                    return el.innerText;
                } else {
                    this.el().forEach(el => {
                        el.innerText = msg;
                    })
                }
            },

            html(data = undefined) {
                if (data === undefined) {
                    return el.innerHTML;
                } else {
                    this.el().forEach(el => {
                        el.innerHTML = data;
                    })
                }
            }
        }
    };

    const emojiObj = {
        'Smileys': [
            {
                "emoji": "😀",
                "title": "grinning face"
            },
            {
                "emoji": "😃",
                "title": "grinning face with big eyes"
            },
            {
                "emoji": "😄",
                "title": "grinning face with smiling eyes"
            },
            {
                "emoji": "😁",
                "title": "beaming face with smiling eyes"
            },
            {
                "emoji": "😆",
                "title": "grinning squinting face"
            },
            {
                "emoji": "😅",
                "title": "grinning face with sweat"
            },
            {
                "emoji": "🤣",
                "title": "rolling on the floor laughing"
            },
            {
                "emoji": "😂",
                "title": "face with tears of joy"
            },
            {
                "emoji": "🙂",
                "title": "slightly smiling face"
            },
            {
                "emoji": "🙃",
                "title": "upside-down face"
            },
            {
                "emoji": "🫠",
                "title": "melting face"
            },
            {
                "emoji": "🫫",
                "v": 18,
                "title": "cracking face"
            },
            {
                "emoji": "😉",
                "title": "winking face"
            },
            {
                "emoji": "😊",
                "title": "smiling face with smiling eyes"
            },
            {
                "emoji": "😇",
                "title": "smiling face with halo"
            },
            {
                "emoji": "🥰",
                "title": "smiling face with hearts"
            },
            {
                "emoji": "😍",
                "title": "smiling face with heart-eyes"
            },
            {
                "emoji": "🤩",
                "title": "star-struck"
            },
            {
                "emoji": "😘",
                "title": "face blowing a kiss"
            },
            {
                "emoji": "😗",
                "title": "kissing face"
            },
            {
                "emoji": "☺️",
                "title": "smiling face"
            },
            {
                "emoji": "😚",
                "title": "kissing face with closed eyes"
            },
            {
                "emoji": "😙",
                "title": "kissing face with smiling eyes"
            },
            {
                "emoji": "🥲",
                "title": "smiling face with tear"
            },
            {
                "emoji": "😋",
                "title": "face savoring food"
            },
            {
                "emoji": "😛",
                "title": "face with tongue"
            },
            {
                "emoji": "😜",
                "title": "winking face with tongue"
            },
            {
                "emoji": "🤪",
                "title": "zany face"
            },
            {
                "emoji": "😝",
                "title": "squinting face with tongue"
            },
            {
                "emoji": "🤑",
                "title": "money-mouth face"
            },
            {
                "emoji": "🤗",
                "title": "smiling face with open hands"
            },
            {
                "emoji": "🤭",
                "title": "face with hand over mouth"
            },
            {
                "emoji": "🫢",
                "title": "face with open eyes and hand over mouth"
            },
            {
                "emoji": "🫣",
                "title": "face with peeking eye"
            },
            {
                "emoji": "🤫",
                "title": "shushing face"
            },
            {
                "emoji": "🤔",
                "title": "thinking face"
            },
            {
                "emoji": "🫡",
                "title": "saluting face"
            },
            {
                "emoji": "🤐",
                "title": "zipper-mouth face"
            },
            {
                "emoji": "🤨",
                "title": "face with raised eyebrow"
            },
            {
                "emoji": "😐",
                "title": "neutral face"
            },
            {
                "emoji": "😑",
                "title": "expressionless face"
            },
            {
                "emoji": "😶",
                "title": "face without mouth"
            },
            {
                "emoji": "🫥",
                "title": "dotted line face"
            },
            {
                "emoji": "😶‍🌫️",
                "title": "face in clouds"
            },
            {
                "emoji": "😏",
                "title": "smirking face"
            },
            {
                "emoji": "😒",
                "title": "unamused face"
            },
            {
                "emoji": "🙄",
                "title": "face with rolling eyes"
            },
            {
                "emoji": "😬",
                "title": "grimacing face"
            },
            {
                "emoji": "😮‍💨",
                "title": "face exhaling"
            },
            {
                "emoji": "🤥",
                "title": "lying face"
            },
            {
                "emoji": "🫨",
                "title": "shaking face"
            },
            {
                "emoji": "🙂‍↔️",
                "title": "head shaking horizontally"
            },
            {
                "emoji": "🙂‍↕️",
                "title": "head shaking vertically"
            },
            {
                "emoji": "😌",
                "title": "relieved face"
            },
            {
                "emoji": "😔",
                "title": "pensive face"
            },
            {
                "emoji": "😪",
                "title": "sleepy face"
            },
            {
                "emoji": "🤤",
                "title": "drooling face"
            },
            {
                "emoji": "😴",
                "title": "sleeping face"
            },
            {
                "emoji": "🫩",
                "title": "face with bags under eyes"
            },
            {
                "emoji": "😷",
                "title": "face with medical mask"
            },
            {
                "emoji": "🤒",
                "title": "face with thermometer"
            },
            {
                "emoji": "🤕",
                "title": "face with head-bandage"
            },
            {
                "emoji": "🤢",
                "title": "nauseated face"
            },
            {
                "emoji": "🤮",
                "title": "face vomiting"
            },
            {
                "emoji": "🤧",
                "title": "sneezing face"
            },
            {
                "emoji": "🥵",
                "title": "hot face"
            },
            {
                "emoji": "🥶",
                "title": "cold face"
            },
            {
                "emoji": "🥴",
                "title": "woozy face"
            },
            {
                "emoji": "😵",
                "title": "face with crossed-out eyes"
            },
            {
                "emoji": "😵‍💫",
                "title": "face with spiral eyes"
            },
            {
                "emoji": "🤯",
                "title": "exploding head"
            },
            {
                "emoji": "🤠",
                "title": "cowboy hat face"
            },
            {
                "emoji": "🥳",
                "title": "partying face"
            },
            {
                "emoji": "🥸",
                "title": "disguised face"
            },
            {
                "emoji": "😎",
                "title": "smiling face with sunglasses"
            },
            {
                "emoji": "🤓",
                "title": "nerd face"
            },
            {
                "emoji": "🧐",
                "title": "face with monocle"
            },
            {
                "emoji": "😕",
                "title": "confused face"
            },
            {
                "emoji": "🫤",
                "title": "face with diagonal mouth"
            },
            {
                "emoji": "😟",
                "title": "worried face"
            },
            {
                "emoji": "🙁",
                "title": "slightly frowning face"
            },
            {
                "emoji": "☹️",
                "title": "frowning face"
            },
            {
                "emoji": "😮",
                "title": "face with open mouth"
            },
            {
                "emoji": "😯",
                "title": "hushed face"
            },
            {
                "emoji": "😲",
                "title": "astonished face"
            },
            {
                "emoji": "😳",
                "title": "flushed face"
            },
            {
                "emoji": "🫪",
                "v": 17,
                "title": "distorted face"
            },
            {
                "emoji": "🥺",
                "title": "pleading face"
            },
            {
                "emoji": "🥹",
                "title": "face holding back tears"
            },
            {
                "emoji": "😦",
                "title": "frowning face with open mouth"
            },
            {
                "emoji": "😧",
                "title": "anguished face"
            },
            {
                "emoji": "😨",
                "title": "fearful face"
            },
            {
                "emoji": "😰",
                "title": "anxious face with sweat"
            },
            {
                "emoji": "😥",
                "title": "sad but relieved face"
            },
            {
                "emoji": "😢",
                "title": "crying face"
            },
            {
                "emoji": "😭",
                "title": "loudly crying face"
            },
            {
                "emoji": "😱",
                "title": "face screaming in fear"
            },
            {
                "emoji": "😖",
                "title": "confounded face"
            },
            {
                "emoji": "😣",
                "title": "persevering face"
            },
            {
                "emoji": "😞",
                "title": "disappointed face"
            },
            {
                "emoji": "😓",
                "title": "downcast face with sweat"
            },
            {
                "emoji": "😩",
                "title": "weary face"
            },
            {
                "emoji": "😫",
                "title": "tired face"
            },
            {
                "emoji": "🥱",
                "title": "yawning face"
            },
            {
                "emoji": "😤",
                "title": "face with steam from nose"
            },
            {
                "emoji": "😡",
                "title": "enraged face"
            },
            {
                "emoji": "😠",
                "title": "angry face"
            },
            {
                "emoji": "🤬",
                "title": "face with symbols on mouth"
            },
            {
                "emoji": "😈",
                "title": "smiling face with horns"
            },
            {
                "emoji": "👿",
                "title": "angry face with horns"
            },
            {
                "emoji": "💀",
                "title": "skull"
            },
            {
                "emoji": "☠️",
                "title": "skull and crossbones"
            },
            {
                "emoji": "💩",
                "title": "pile of poo"
            },
            {
                "emoji": "🤡",
                "title": "clown face"
            },
            {
                "emoji": "👹",
                "title": "ogre"
            },
            {
                "emoji": "👺",
                "title": "goblin"
            },
            {
                "emoji": "👻",
                "title": "ghost"
            },
            {
                "emoji": "👽",
                "title": "alien"
            },
            {
                "emoji": "👾",
                "title": "alien monster"
            },
            {
                "emoji": "🤖",
                "title": "robot"
            },
            {
                "emoji": "😺",
                "title": "grinning cat"
            },
            {
                "emoji": "😸",
                "title": "grinning cat with smiling eyes"
            },
            {
                "emoji": "😹",
                "title": "cat with tears of joy"
            },
            {
                "emoji": "😻",
                "title": "smiling cat with heart-eyes"
            },
            {
                "emoji": "😼",
                "title": "cat with wry smile"
            },
            {
                "emoji": "😽",
                "title": "kissing cat"
            },
            {
                "emoji": "🙀",
                "title": "weary cat"
            },
            {
                "emoji": "😿",
                "title": "crying cat"
            },
            {
                "emoji": "😾",
                "title": "pouting cat"
            },
            {
                "emoji": "🙈",
                "title": "see-no-evil monkey"
            },
            {
                "emoji": "🙉",
                "title": "hear-no-evil monkey"
            },
            {
                "emoji": "🙊",
                "title": "speak-no-evil monkey"
            },
            {
                "emoji": "💌",
                "title": "love letter"
            },
            {
                "emoji": "💘",
                "title": "heart with arrow"
            },
            {
                "emoji": "💝",
                "title": "heart with ribbon"
            },
            {
                "emoji": "💖",
                "title": "sparkling heart"
            },
            {
                "emoji": "💗",
                "title": "growing heart"
            },
            {
                "emoji": "💓",
                "title": "beating heart"
            },
            {
                "emoji": "💞",
                "title": "revolving hearts"
            },
            {
                "emoji": "💕",
                "title": "two hearts"
            },
            {
                "emoji": "💟",
                "title": "heart decoration"
            },
            {
                "emoji": "❣️",
                "title": "heart exclamation"
            },
            {
                "emoji": "💔",
                "title": "broken heart"
            },
            {
                "emoji": "❤️‍🔥",
                "title": "heart on fire"
            },
            {
                "emoji": "❤️‍🩹",
                "title": "mending heart"
            },
            {
                "emoji": "❤️",
                "title": "red heart"
            },
            {
                "emoji": "🩷",
                "title": "pink heart"
            },
            {
                "emoji": "🧡",
                "title": "orange heart"
            },
            {
                "emoji": "💛",
                "title": "yellow heart"
            },
            {
                "emoji": "💚",
                "title": "green heart"
            },
            {
                "emoji": "💙",
                "title": "blue heart"
            },
            {
                "emoji": "🩵",
                "title": "light blue heart"
            },
            {
                "emoji": "💜",
                "title": "purple heart"
            },
            {
                "emoji": "🤎",
                "title": "brown heart"
            },
            {
                "emoji": "🖤",
                "title": "black heart"
            },
            {
                "emoji": "🩶",
                "title": "grey heart"
            },
            {
                "emoji": "🤍",
                "title": "white heart"
            },
            {
                "emoji": "💋",
                "title": "kiss mark"
            },
            {
                "emoji": "💯",
                "title": "hundred points"
            },
            {
                "emoji": "💢",
                "title": "anger symbol"
            },
            {
                "emoji": "🫯",
                "v": 17,
                "title": "fight cloud"
            },
            {
                "emoji": "💥",
                "title": "collision"
            },
            {
                "emoji": "💫",
                "title": "dizzy"
            },
            {
                "emoji": "💦",
                "title": "sweat droplets"
            },
            {
                "emoji": "💨",
                "title": "dashing away"
            },
            {
                "emoji": "🕳️",
                "title": "hole"
            },
            {
                "emoji": "💬",
                "title": "speech balloon"
            },
            {
                "emoji": "👁️‍🗨️",
                "title": "eye in speech bubble"
            },
            {
                "emoji": "🗨️",
                "title": "left speech bubble"
            },
            {
                "emoji": "🗯️",
                "title": "right anger bubble"
            },
            {
                "emoji": "💭",
                "title": "thought balloon"
            },
            {
                "emoji": "💤",
                "title": "ZZZ"
            }
        ],
        'People': [
            {
                "emoji": "👋",
                "title": "waving hand",
                "tone": true
            },
            {
                "emoji": "🤚",
                "title": "raised back of hand",
                "tone": true
            },
            {
                "emoji": "🖐️",
                "title": "hand with fingers splayed",
                "tone": true
            },
            {
                "emoji": "✋",
                "title": "raised hand",
                "tone": true
            },
            {
                "emoji": "🖖",
                "title": "vulcan salute",
                "tone": true
            },
            {
                "emoji": "🫱",
                "title": "rightwards hand",
                "tone": true
            },
            {
                "emoji": "🫲",
                "title": "leftwards hand",
                "tone": true
            },
            {
                "emoji": "🫳",
                "title": "palm down hand",
                "tone": true
            },
            {
                "emoji": "🫴",
                "title": "palm up hand",
                "tone": true
            },
            {
                "emoji": "🫷",
                "title": "leftwards pushing hand",
                "tone": true
            },
            {
                "emoji": "🫸",
                "title": "rightwards pushing hand",
                "tone": true
            },
            {
                "emoji": "👌",
                "title": "OK hand",
                "tone": true
            },
            {
                "emoji": "🤌",
                "title": "pinched fingers",
                "tone": true
            },
            {
                "emoji": "🤏",
                "title": "pinching hand",
                "tone": true
            },
            {
                "emoji": "✌️",
                "title": "victory hand",
                "tone": true
            },
            {
                "emoji": "🤞",
                "title": "crossed fingers",
                "tone": true
            },
            {
                "emoji": "🫰",
                "title": "hand with index finger and thumb crossed",
                "tone": true
            },
            {
                "emoji": "🤟",
                "title": "love-you gesture",
                "tone": true
            },
            {
                "emoji": "🤘",
                "title": "sign of the horns",
                "tone": true
            },
            {
                "emoji": "🤙",
                "title": "call me hand",
                "tone": true
            },
            {
                "emoji": "👈",
                "title": "backhand index pointing left",
                "tone": true
            },
            {
                "emoji": "👉",
                "title": "backhand index pointing right",
                "tone": true
            },
            {
                "emoji": "👆",
                "title": "backhand index pointing up",
                "tone": true
            },
            {
                "emoji": "🖕",
                "title": "middle finger",
                "tone": true
            },
            {
                "emoji": "👇",
                "title": "backhand index pointing down",
                "tone": true
            },
            {
                "emoji": "☝️",
                "title": "index pointing up",
                "tone": true
            },
            {
                "emoji": "🫵",
                "title": "index pointing at the viewer",
                "tone": true
            },
            {
                "emoji": "👍",
                "title": "thumbs up",
                "tone": true
            },
            {
                "emoji": "👎",
                "title": "thumbs down",
                "tone": true
            },
            {
                "emoji": "🫹",
                "v": 18,
                "title": "leftwards thumb sign",
                "tone": true
            },
            {
                "emoji": "🫺",
                "v": 18,
                "title": "rightwards thumb sign",
                "tone": true
            },
            {
                "emoji": "✊",
                "title": "raised fist",
                "tone": true
            },
            {
                "emoji": "👊",
                "title": "oncoming fist",
                "tone": true
            },
            {
                "emoji": "🤛",
                "title": "left-facing fist",
                "tone": true
            },
            {
                "emoji": "🤜",
                "title": "right-facing fist",
                "tone": true
            },
            {
                "emoji": "👏",
                "title": "clapping hands",
                "tone": true
            },
            {
                "emoji": "🙌",
                "title": "raising hands",
                "tone": true
            },
            {
                "emoji": "🫶",
                "title": "heart hands",
                "tone": true
            },
            {
                "emoji": "👐",
                "title": "open hands",
                "tone": true
            },
            {
                "emoji": "🤲",
                "title": "palms up together",
                "tone": true
            },
            {
                "emoji": "🤝",
                "title": "handshake",
                "tone": true
            },
            {
                "emoji": "🙏",
                "title": "folded hands",
                "tone": true
            },
            {
                "emoji": "✍️",
                "title": "writing hand",
                "tone": true
            },
            {
                "emoji": "💅",
                "title": "nail polish",
                "tone": true
            },
            {
                "emoji": "🤳",
                "title": "selfie",
                "tone": true
            },
            {
                "emoji": "💪",
                "title": "flexed biceps",
                "tone": true
            },
            {
                "emoji": "🦾",
                "title": "mechanical arm"
            },
            {
                "emoji": "🦿",
                "title": "mechanical leg"
            },
            {
                "emoji": "🦵",
                "title": "leg",
                "tone": true
            },
            {
                "emoji": "🦶",
                "title": "foot",
                "tone": true
            },
            {
                "emoji": "👂",
                "title": "ear",
                "tone": true
            },
            {
                "emoji": "🦻",
                "title": "ear with hearing aid",
                "tone": true
            },
            {
                "emoji": "👃",
                "title": "nose",
                "tone": true
            },
            {
                "emoji": "🧠",
                "title": "brain"
            },
            {
                "emoji": "🫀",
                "title": "anatomical heart"
            },
            {
                "emoji": "🫁",
                "title": "lungs"
            },
            {
                "emoji": "🦷",
                "title": "tooth"
            },
            {
                "emoji": "🦴",
                "title": "bone"
            },
            {
                "emoji": "👀",
                "title": "eyes"
            },
            {
                "emoji": "👁️",
                "title": "eye"
            },
            {
                "emoji": "👅",
                "title": "tongue"
            },
            {
                "emoji": "👄",
                "title": "mouth"
            },
            {
                "emoji": "🫦",
                "title": "biting lip"
            },
            {
                "emoji": "👶",
                "title": "baby",
                "tone": true
            },
            {
                "emoji": "🧒",
                "title": "child",
                "tone": true
            },
            {
                "emoji": "👦",
                "title": "boy",
                "tone": true
            },
            {
                "emoji": "👧",
                "title": "girl",
                "tone": true
            },
            {
                "emoji": "🧑",
                "title": "person",
                "tone": true
            },
            {
                "emoji": "👱",
                "title": "person: blond hair",
                "tone": true
            },
            {
                "emoji": "👨",
                "title": "man",
                "tone": true
            },
            {
                "emoji": "🧔",
                "title": "person: beard",
                "tone": true
            },
            {
                "emoji": "🧔‍♂️",
                "title": "man: beard",
                "tone": true
            },
            {
                "emoji": "🧔‍♀️",
                "title": "woman: beard",
                "tone": true
            },
            {
                "emoji": "👨‍🦰",
                "title": "man: red hair",
                "tone": true
            },
            {
                "emoji": "👨‍🦱",
                "title": "man: curly hair",
                "tone": true
            },
            {
                "emoji": "👨‍🦳",
                "title": "man: white hair",
                "tone": true
            },
            {
                "emoji": "👨‍🦲",
                "title": "man: bald",
                "tone": true
            },
            {
                "emoji": "👩",
                "title": "woman",
                "tone": true
            },
            {
                "emoji": "👩‍🦰",
                "title": "woman: red hair",
                "tone": true
            },
            {
                "emoji": "🧑‍🦰",
                "title": "person: red hair",
                "tone": true
            },
            {
                "emoji": "👩‍🦱",
                "title": "woman: curly hair",
                "tone": true
            },
            {
                "emoji": "🧑‍🦱",
                "title": "person: curly hair",
                "tone": true
            },
            {
                "emoji": "👩‍🦳",
                "title": "woman: white hair",
                "tone": true
            },
            {
                "emoji": "🧑‍🦳",
                "title": "person: white hair",
                "tone": true
            },
            {
                "emoji": "👩‍🦲",
                "title": "woman: bald",
                "tone": true
            },
            {
                "emoji": "🧑‍🦲",
                "title": "person: bald",
                "tone": true
            },
            {
                "emoji": "👱‍♀️",
                "title": "woman: blond hair",
                "tone": true
            },
            {
                "emoji": "👱‍♂️",
                "title": "man: blond hair",
                "tone": true
            },
            {
                "emoji": "🧓",
                "title": "older person",
                "tone": true
            },
            {
                "emoji": "👴",
                "title": "old man",
                "tone": true
            },
            {
                "emoji": "👵",
                "title": "old woman",
                "tone": true
            },
            {
                "emoji": "🙍",
                "title": "person frowning",
                "tone": true
            },
            {
                "emoji": "🙍‍♂️",
                "title": "man frowning",
                "tone": true
            },
            {
                "emoji": "🙍‍♀️",
                "title": "woman frowning",
                "tone": true
            },
            {
                "emoji": "🙎",
                "title": "person pouting",
                "tone": true
            },
            {
                "emoji": "🙎‍♂️",
                "title": "man pouting",
                "tone": true
            },
            {
                "emoji": "🙎‍♀️",
                "title": "woman pouting",
                "tone": true
            },
            {
                "emoji": "🙅",
                "title": "person gesturing NO",
                "tone": true
            },
            {
                "emoji": "🙅‍♂️",
                "title": "man gesturing NO",
                "tone": true
            },
            {
                "emoji": "🙅‍♀️",
                "title": "woman gesturing NO",
                "tone": true
            },
            {
                "emoji": "🙆",
                "title": "person gesturing OK",
                "tone": true
            },
            {
                "emoji": "🙆‍♂️",
                "title": "man gesturing OK",
                "tone": true
            },
            {
                "emoji": "🙆‍♀️",
                "title": "woman gesturing OK",
                "tone": true
            },
            {
                "emoji": "💁",
                "title": "person tipping hand",
                "tone": true
            },
            {
                "emoji": "💁‍♂️",
                "title": "man tipping hand",
                "tone": true
            },
            {
                "emoji": "💁‍♀️",
                "title": "woman tipping hand",
                "tone": true
            },
            {
                "emoji": "🙋",
                "title": "person raising hand",
                "tone": true
            },
            {
                "emoji": "🙋‍♂️",
                "title": "man raising hand",
                "tone": true
            },
            {
                "emoji": "🙋‍♀️",
                "title": "woman raising hand",
                "tone": true
            },
            {
                "emoji": "🧏",
                "title": "deaf person",
                "tone": true
            },
            {
                "emoji": "🧏‍♂️",
                "title": "deaf man",
                "tone": true
            },
            {
                "emoji": "🧏‍♀️",
                "title": "deaf woman",
                "tone": true
            },
            {
                "emoji": "🙇",
                "title": "person bowing",
                "tone": true
            },
            {
                "emoji": "🙇‍♂️",
                "title": "man bowing",
                "tone": true
            },
            {
                "emoji": "🙇‍♀️",
                "title": "woman bowing",
                "tone": true
            },
            {
                "emoji": "🤦",
                "title": "person facepalming",
                "tone": true
            },
            {
                "emoji": "🤦‍♂️",
                "title": "man facepalming",
                "tone": true
            },
            {
                "emoji": "🤦‍♀️",
                "title": "woman facepalming",
                "tone": true
            },
            {
                "emoji": "🤷",
                "title": "person shrugging",
                "tone": true
            },
            {
                "emoji": "🤷‍♂️",
                "title": "man shrugging",
                "tone": true
            },
            {
                "emoji": "🤷‍♀️",
                "title": "woman shrugging",
                "tone": true
            },
            {
                "emoji": "🧑‍⚕️",
                "title": "health worker",
                "tone": true
            },
            {
                "emoji": "👨‍⚕️",
                "title": "man health worker",
                "tone": true
            },
            {
                "emoji": "👩‍⚕️",
                "title": "woman health worker",
                "tone": true
            },
            {
                "emoji": "🧑‍🎓",
                "title": "student",
                "tone": true
            },
            {
                "emoji": "👨‍🎓",
                "title": "man student",
                "tone": true
            },
            {
                "emoji": "👩‍🎓",
                "title": "woman student",
                "tone": true
            },
            {
                "emoji": "🧑‍🏫",
                "title": "teacher",
                "tone": true
            },
            {
                "emoji": "👨‍🏫",
                "title": "man teacher",
                "tone": true
            },
            {
                "emoji": "👩‍🏫",
                "title": "woman teacher",
                "tone": true
            },
            {
                "emoji": "🧑‍⚖️",
                "title": "judge",
                "tone": true
            },
            {
                "emoji": "👨‍⚖️",
                "title": "man judge",
                "tone": true
            },
            {
                "emoji": "👩‍⚖️",
                "title": "woman judge",
                "tone": true
            },
            {
                "emoji": "🧑‍🌾",
                "title": "farmer",
                "tone": true
            },
            {
                "emoji": "👨‍🌾",
                "title": "man farmer",
                "tone": true
            },
            {
                "emoji": "👩‍🌾",
                "title": "woman farmer",
                "tone": true
            },
            {
                "emoji": "🧑‍🍳",
                "title": "cook",
                "tone": true
            },
            {
                "emoji": "👨‍🍳",
                "title": "man cook",
                "tone": true
            },
            {
                "emoji": "👩‍🍳",
                "title": "woman cook",
                "tone": true
            },
            {
                "emoji": "🧑‍🔧",
                "title": "mechanic",
                "tone": true
            },
            {
                "emoji": "👨‍🔧",
                "title": "man mechanic",
                "tone": true
            },
            {
                "emoji": "👩‍🔧",
                "title": "woman mechanic",
                "tone": true
            },
            {
                "emoji": "🧑‍🏭",
                "title": "factory worker",
                "tone": true
            },
            {
                "emoji": "👨‍🏭",
                "title": "man factory worker",
                "tone": true
            },
            {
                "emoji": "👩‍🏭",
                "title": "woman factory worker",
                "tone": true
            },
            {
                "emoji": "🧑‍💼",
                "title": "office worker",
                "tone": true
            },
            {
                "emoji": "👨‍💼",
                "title": "man office worker",
                "tone": true
            },
            {
                "emoji": "👩‍💼",
                "title": "woman office worker",
                "tone": true
            },
            {
                "emoji": "🧑‍🔬",
                "title": "scientist",
                "tone": true
            },
            {
                "emoji": "👨‍🔬",
                "title": "man scientist",
                "tone": true
            },
            {
                "emoji": "👩‍🔬",
                "title": "woman scientist",
                "tone": true
            },
            {
                "emoji": "🧑‍💻",
                "title": "technologist",
                "tone": true
            },
            {
                "emoji": "👨‍💻",
                "title": "man technologist",
                "tone": true
            },
            {
                "emoji": "👩‍💻",
                "title": "woman technologist",
                "tone": true
            },
            {
                "emoji": "🧑‍🎤",
                "title": "singer",
                "tone": true
            },
            {
                "emoji": "👨‍🎤",
                "title": "man singer",
                "tone": true
            },
            {
                "emoji": "👩‍🎤",
                "title": "woman singer",
                "tone": true
            },
            {
                "emoji": "🧑‍🎨",
                "title": "artist",
                "tone": true
            },
            {
                "emoji": "👨‍🎨",
                "title": "man artist",
                "tone": true
            },
            {
                "emoji": "👩‍🎨",
                "title": "woman artist",
                "tone": true
            },
            {
                "emoji": "🧑‍✈️",
                "title": "pilot",
                "tone": true
            },
            {
                "emoji": "👨‍✈️",
                "title": "man pilot",
                "tone": true
            },
            {
                "emoji": "👩‍✈️",
                "title": "woman pilot",
                "tone": true
            },
            {
                "emoji": "🧑‍🚀",
                "title": "astronaut",
                "tone": true
            },
            {
                "emoji": "👨‍🚀",
                "title": "man astronaut",
                "tone": true
            },
            {
                "emoji": "👩‍🚀",
                "title": "woman astronaut",
                "tone": true
            },
            {
                "emoji": "🧑‍🚒",
                "title": "firefighter",
                "tone": true
            },
            {
                "emoji": "👨‍🚒",
                "title": "man firefighter",
                "tone": true
            },
            {
                "emoji": "👩‍🚒",
                "title": "woman firefighter",
                "tone": true
            },
            {
                "emoji": "👮",
                "title": "police officer",
                "tone": true
            },
            {
                "emoji": "👮‍♂️",
                "title": "man police officer",
                "tone": true
            },
            {
                "emoji": "👮‍♀️",
                "title": "woman police officer",
                "tone": true
            },
            {
                "emoji": "🕵️",
                "title": "detective",
                "tone": true
            },
            {
                "emoji": "🕵️‍♂️",
                "title": "man detective",
                "tone": true
            },
            {
                "emoji": "🕵️‍♀️",
                "title": "woman detective",
                "tone": true
            },
            {
                "emoji": "💂",
                "title": "guard",
                "tone": true
            },
            {
                "emoji": "💂‍♂️",
                "title": "man guard",
                "tone": true
            },
            {
                "emoji": "💂‍♀️",
                "title": "woman guard",
                "tone": true
            },
            {
                "emoji": "🥷",
                "title": "ninja",
                "tone": true
            },
            {
                "emoji": "👷",
                "title": "construction worker",
                "tone": true
            },
            {
                "emoji": "👷‍♂️",
                "title": "man construction worker",
                "tone": true
            },
            {
                "emoji": "👷‍♀️",
                "title": "woman construction worker",
                "tone": true
            },
            {
                "emoji": "🫅",
                "title": "person with crown",
                "tone": true
            },
            {
                "emoji": "🤴",
                "title": "prince",
                "tone": true
            },
            {
                "emoji": "👸",
                "title": "princess",
                "tone": true
            },
            {
                "emoji": "👳",
                "title": "person wearing turban",
                "tone": true
            },
            {
                "emoji": "👳‍♂️",
                "title": "man wearing turban",
                "tone": true
            },
            {
                "emoji": "👳‍♀️",
                "title": "woman wearing turban",
                "tone": true
            },
            {
                "emoji": "👲",
                "title": "person with skullcap",
                "tone": true
            },
            {
                "emoji": "🧕",
                "title": "woman with headscarf",
                "tone": true
            },
            {
                "emoji": "🤵",
                "title": "person in tuxedo",
                "tone": true
            },
            {
                "emoji": "🤵‍♂️",
                "title": "man in tuxedo",
                "tone": true
            },
            {
                "emoji": "🤵‍♀️",
                "title": "woman in tuxedo",
                "tone": true
            },
            {
                "emoji": "👰",
                "title": "person with veil",
                "tone": true
            },
            {
                "emoji": "👰‍♂️",
                "title": "man with veil",
                "tone": true
            },
            {
                "emoji": "👰‍♀️",
                "title": "woman with veil",
                "tone": true
            },
            {
                "emoji": "🤰",
                "title": "pregnant woman",
                "tone": true
            },
            {
                "emoji": "🫃",
                "title": "pregnant man",
                "tone": true
            },
            {
                "emoji": "🫄",
                "title": "pregnant person",
                "tone": true
            },
            {
                "emoji": "🤱",
                "title": "breast-feeding",
                "tone": true
            },
            {
                "emoji": "👩‍🍼",
                "title": "woman feeding baby",
                "tone": true
            },
            {
                "emoji": "👨‍🍼",
                "title": "man feeding baby",
                "tone": true
            },
            {
                "emoji": "🧑‍🍼",
                "title": "person feeding baby",
                "tone": true
            },
            {
                "emoji": "👼",
                "title": "baby angel",
                "tone": true
            },
            {
                "emoji": "🎅",
                "title": "Santa Claus",
                "tone": true
            },
            {
                "emoji": "🤶",
                "title": "Mrs. Claus",
                "tone": true
            },
            {
                "emoji": "🧑‍🎄",
                "title": "Mx Claus",
                "tone": true
            },
            {
                "emoji": "🦸",
                "title": "superhero",
                "tone": true
            },
            {
                "emoji": "🦸‍♂️",
                "title": "man superhero",
                "tone": true
            },
            {
                "emoji": "🦸‍♀️",
                "title": "woman superhero",
                "tone": true
            },
            {
                "emoji": "🦹",
                "title": "supervillain",
                "tone": true
            },
            {
                "emoji": "🦹‍♂️",
                "title": "man supervillain",
                "tone": true
            },
            {
                "emoji": "🦹‍♀️",
                "title": "woman supervillain",
                "tone": true
            },
            {
                "emoji": "🧙",
                "title": "mage",
                "tone": true
            },
            {
                "emoji": "🧙‍♂️",
                "title": "man mage",
                "tone": true
            },
            {
                "emoji": "🧙‍♀️",
                "title": "woman mage",
                "tone": true
            },
            {
                "emoji": "🧚",
                "title": "fairy",
                "tone": true
            },
            {
                "emoji": "🧚‍♂️",
                "title": "man fairy",
                "tone": true
            },
            {
                "emoji": "🧚‍♀️",
                "title": "woman fairy",
                "tone": true
            },
            {
                "emoji": "🧛",
                "title": "vampire",
                "tone": true
            },
            {
                "emoji": "🧛‍♂️",
                "title": "man vampire",
                "tone": true
            },
            {
                "emoji": "🧛‍♀️",
                "title": "woman vampire",
                "tone": true
            },
            {
                "emoji": "🧜",
                "title": "merperson",
                "tone": true
            },
            {
                "emoji": "🧜‍♂️",
                "title": "merman",
                "tone": true
            },
            {
                "emoji": "🧜‍♀️",
                "title": "mermaid",
                "tone": true
            },
            {
                "emoji": "🧝",
                "title": "elf",
                "tone": true
            },
            {
                "emoji": "🧝‍♂️",
                "title": "man elf",
                "tone": true
            },
            {
                "emoji": "🧝‍♀️",
                "title": "woman elf",
                "tone": true
            },
            {
                "emoji": "🧞",
                "title": "genie"
            },
            {
                "emoji": "🧞‍♂️",
                "title": "man genie"
            },
            {
                "emoji": "🧞‍♀️",
                "title": "woman genie"
            },
            {
                "emoji": "🧟",
                "title": "zombie"
            },
            {
                "emoji": "🧟‍♂️",
                "title": "man zombie"
            },
            {
                "emoji": "🧟‍♀️",
                "title": "woman zombie"
            },
            {
                "emoji": "🧌",
                "title": "troll"
            },
            {
                "emoji": "🫈",
                "v": 17,
                "title": "hairy creature"
            },
            {
                "emoji": "💆",
                "title": "person getting massage",
                "tone": true
            },
            {
                "emoji": "💆‍♂️",
                "title": "man getting massage",
                "tone": true
            },
            {
                "emoji": "💆‍♀️",
                "title": "woman getting massage",
                "tone": true
            },
            {
                "emoji": "💇",
                "title": "person getting haircut",
                "tone": true
            },
            {
                "emoji": "💇‍♂️",
                "title": "man getting haircut",
                "tone": true
            },
            {
                "emoji": "💇‍♀️",
                "title": "woman getting haircut",
                "tone": true
            },
            {
                "emoji": "🚶",
                "title": "person walking",
                "tone": true
            },
            {
                "emoji": "🚶‍♂️",
                "title": "man walking",
                "tone": true
            },
            {
                "emoji": "🚶‍♀️",
                "title": "woman walking",
                "tone": true
            },
            {
                "emoji": "🚶‍➡️",
                "title": "person walking facing right",
                "tone": true
            },
            {
                "emoji": "🚶‍♀️‍➡️",
                "title": "woman walking facing right",
                "tone": true
            },
            {
                "emoji": "🚶‍♂️‍➡️",
                "title": "man walking facing right",
                "tone": true
            },
            {
                "emoji": "🧍",
                "title": "person standing",
                "tone": true
            },
            {
                "emoji": "🧍‍♂️",
                "title": "man standing",
                "tone": true
            },
            {
                "emoji": "🧍‍♀️",
                "title": "woman standing",
                "tone": true
            },
            {
                "emoji": "🧎",
                "title": "person kneeling",
                "tone": true
            },
            {
                "emoji": "🧎‍♂️",
                "title": "man kneeling",
                "tone": true
            },
            {
                "emoji": "🧎‍♀️",
                "title": "woman kneeling",
                "tone": true
            },
            {
                "emoji": "🧎‍➡️",
                "title": "person kneeling facing right",
                "tone": true
            },
            {
                "emoji": "🧎‍♀️‍➡️",
                "title": "woman kneeling facing right",
                "tone": true
            },
            {
                "emoji": "🧎‍♂️‍➡️",
                "title": "man kneeling facing right",
                "tone": true
            },
            {
                "emoji": "🧑‍🦯",
                "title": "person with white cane",
                "tone": true
            },
            {
                "emoji": "🧑‍🦯‍➡️",
                "title": "person with white cane facing right",
                "tone": true
            },
            {
                "emoji": "👨‍🦯",
                "title": "man with white cane",
                "tone": true
            },
            {
                "emoji": "👨‍🦯‍➡️",
                "title": "man with white cane facing right",
                "tone": true
            },
            {
                "emoji": "👩‍🦯",
                "title": "woman with white cane",
                "tone": true
            },
            {
                "emoji": "👩‍🦯‍➡️",
                "title": "woman with white cane facing right",
                "tone": true
            },
            {
                "emoji": "🧑‍🦼",
                "title": "person in motorized wheelchair",
                "tone": true
            },
            {
                "emoji": "🧑‍🦼‍➡️",
                "title": "person in motorized wheelchair facing right",
                "tone": true
            },
            {
                "emoji": "👨‍🦼",
                "title": "man in motorized wheelchair",
                "tone": true
            },
            {
                "emoji": "👨‍🦼‍➡️",
                "title": "man in motorized wheelchair facing right",
                "tone": true
            },
            {
                "emoji": "👩‍🦼",
                "title": "woman in motorized wheelchair",
                "tone": true
            },
            {
                "emoji": "👩‍🦼‍➡️",
                "title": "woman in motorized wheelchair facing right",
                "tone": true
            },
            {
                "emoji": "🧑‍🦽",
                "title": "person in manual wheelchair",
                "tone": true
            },
            {
                "emoji": "🧑‍🦽‍➡️",
                "title": "person in manual wheelchair facing right",
                "tone": true
            },
            {
                "emoji": "👨‍🦽",
                "title": "man in manual wheelchair",
                "tone": true
            },
            {
                "emoji": "👨‍🦽‍➡️",
                "title": "man in manual wheelchair facing right",
                "tone": true
            },
            {
                "emoji": "👩‍🦽",
                "title": "woman in manual wheelchair",
                "tone": true
            },
            {
                "emoji": "👩‍🦽‍➡️",
                "title": "woman in manual wheelchair facing right",
                "tone": true
            },
            {
                "emoji": "🏃",
                "title": "person running",
                "tone": true
            },
            {
                "emoji": "🏃‍♂️",
                "title": "man running",
                "tone": true
            },
            {
                "emoji": "🏃‍♀️",
                "title": "woman running",
                "tone": true
            },
            {
                "emoji": "🏃‍➡️",
                "title": "person running facing right",
                "tone": true
            },
            {
                "emoji": "🏃‍♀️‍➡️",
                "title": "woman running facing right",
                "tone": true
            },
            {
                "emoji": "🏃‍♂️‍➡️",
                "title": "man running facing right",
                "tone": true
            },
            {
                "emoji": "🧑‍🩰",
                "v": 17,
                "title": "ballet dancer",
                "tone": true
            },
            {
                "emoji": "💃",
                "title": "woman dancing",
                "tone": true
            },
            {
                "emoji": "🕺",
                "title": "man dancing",
                "tone": true
            },
            {
                "emoji": "🕴️",
                "title": "person in suit levitating",
                "tone": true
            },
            {
                "emoji": "👯",
                "title": "people with bunny ears",
                "tone": true
            },
            {
                "emoji": "👯‍♂️",
                "title": "men with bunny ears",
                "tone": true
            },
            {
                "emoji": "👯‍♀️",
                "title": "women with bunny ears",
                "tone": true
            },
            {
                "emoji": "🧖",
                "title": "person in steamy room",
                "tone": true
            },
            {
                "emoji": "🧖‍♂️",
                "title": "man in steamy room",
                "tone": true
            },
            {
                "emoji": "🧖‍♀️",
                "title": "woman in steamy room",
                "tone": true
            },
            {
                "emoji": "🧗",
                "title": "person climbing",
                "tone": true
            },
            {
                "emoji": "🧗‍♂️",
                "title": "man climbing",
                "tone": true
            },
            {
                "emoji": "🧗‍♀️",
                "title": "woman climbing",
                "tone": true
            },
            {
                "emoji": "🤺",
                "title": "person fencing"
            },
            {
                "emoji": "🏇",
                "title": "horse racing",
                "tone": true
            },
            {
                "emoji": "⛷️",
                "title": "skier"
            },
            {
                "emoji": "🏂",
                "title": "snowboarder",
                "tone": true
            },
            {
                "emoji": "🏌️",
                "title": "person golfing",
                "tone": true
            },
            {
                "emoji": "🏌️‍♂️",
                "title": "man golfing",
                "tone": true
            },
            {
                "emoji": "🏌️‍♀️",
                "title": "woman golfing",
                "tone": true
            },
            {
                "emoji": "🏄",
                "title": "person surfing",
                "tone": true
            },
            {
                "emoji": "🏄‍♂️",
                "title": "man surfing",
                "tone": true
            },
            {
                "emoji": "🏄‍♀️",
                "title": "woman surfing",
                "tone": true
            },
            {
                "emoji": "🚣",
                "title": "person rowing boat",
                "tone": true
            },
            {
                "emoji": "🚣‍♂️",
                "title": "man rowing boat",
                "tone": true
            },
            {
                "emoji": "🚣‍♀️",
                "title": "woman rowing boat",
                "tone": true
            },
            {
                "emoji": "🏊",
                "title": "person swimming",
                "tone": true
            },
            {
                "emoji": "🏊‍♂️",
                "title": "man swimming",
                "tone": true
            },
            {
                "emoji": "🏊‍♀️",
                "title": "woman swimming",
                "tone": true
            },
            {
                "emoji": "⛹️",
                "title": "person bouncing ball",
                "tone": true
            },
            {
                "emoji": "⛹️‍♂️",
                "title": "man bouncing ball",
                "tone": true
            },
            {
                "emoji": "⛹️‍♀️",
                "title": "woman bouncing ball",
                "tone": true
            },
            {
                "emoji": "🏋️",
                "title": "person lifting weights",
                "tone": true
            },
            {
                "emoji": "🏋️‍♂️",
                "title": "man lifting weights",
                "tone": true
            },
            {
                "emoji": "🏋️‍♀️",
                "title": "woman lifting weights",
                "tone": true
            },
            {
                "emoji": "🚴",
                "title": "person biking",
                "tone": true
            },
            {
                "emoji": "🚴‍♂️",
                "title": "man biking",
                "tone": true
            },
            {
                "emoji": "🚴‍♀️",
                "title": "woman biking",
                "tone": true
            },
            {
                "emoji": "🚵",
                "title": "person mountain biking",
                "tone": true
            },
            {
                "emoji": "🚵‍♂️",
                "title": "man mountain biking",
                "tone": true
            },
            {
                "emoji": "🚵‍♀️",
                "title": "woman mountain biking",
                "tone": true
            },
            {
                "emoji": "🤸",
                "title": "person cartwheeling",
                "tone": true
            },
            {
                "emoji": "🤸‍♂️",
                "title": "man cartwheeling",
                "tone": true
            },
            {
                "emoji": "🤸‍♀️",
                "title": "woman cartwheeling",
                "tone": true
            },
            {
                "emoji": "🤼",
                "title": "people wrestling",
                "tone": true
            },
            {
                "emoji": "🤼‍♂️",
                "title": "men wrestling",
                "tone": true
            },
            {
                "emoji": "🤼‍♀️",
                "title": "women wrestling",
                "tone": true
            },
            {
                "emoji": "🤽",
                "title": "person playing water polo",
                "tone": true
            },
            {
                "emoji": "🤽‍♂️",
                "title": "man playing water polo",
                "tone": true
            },
            {
                "emoji": "🤽‍♀️",
                "title": "woman playing water polo",
                "tone": true
            },
            {
                "emoji": "🤾",
                "title": "person playing handball",
                "tone": true
            },
            {
                "emoji": "🤾‍♂️",
                "title": "man playing handball",
                "tone": true
            },
            {
                "emoji": "🤾‍♀️",
                "title": "woman playing handball",
                "tone": true
            },
            {
                "emoji": "🤹",
                "title": "person juggling",
                "tone": true
            },
            {
                "emoji": "🤹‍♂️",
                "title": "man juggling",
                "tone": true
            },
            {
                "emoji": "🤹‍♀️",
                "title": "woman juggling",
                "tone": true
            },
            {
                "emoji": "🧘",
                "title": "person in lotus position",
                "tone": true
            },
            {
                "emoji": "🧘‍♂️",
                "title": "man in lotus position",
                "tone": true
            },
            {
                "emoji": "🧘‍♀️",
                "title": "woman in lotus position",
                "tone": true
            },
            {
                "emoji": "🛀",
                "title": "person taking bath",
                "tone": true
            },
            {
                "emoji": "🛌",
                "title": "person in bed",
                "tone": true
            },
            {
                "emoji": "🧑‍🤝‍🧑",
                "title": "people holding hands",
                "tone": true
            },
            {
                "emoji": "👭",
                "title": "women holding hands",
                "tone": true
            },
            {
                "emoji": "👫",
                "title": "woman and man holding hands",
                "tone": true
            },
            {
                "emoji": "👬",
                "title": "men holding hands",
                "tone": true
            },
            {
                "emoji": "💏",
                "title": "kiss",
                "tone": true
            },
            {
                "emoji": "👩‍❤️‍💋‍👨",
                "title": "kiss: woman, man",
                "tone": true
            },
            {
                "emoji": "👨‍❤️‍💋‍👨",
                "title": "kiss: man, man",
                "tone": true
            },
            {
                "emoji": "👩‍❤️‍💋‍👩",
                "title": "kiss: woman, woman",
                "tone": true
            },
            {
                "emoji": "💑",
                "title": "couple with heart",
                "tone": true
            },
            {
                "emoji": "👩‍❤️‍👨",
                "title": "couple with heart: woman, man",
                "tone": true
            },
            {
                "emoji": "👨‍❤️‍👨",
                "title": "couple with heart: man, man",
                "tone": true
            },
            {
                "emoji": "👩‍❤️‍👩",
                "title": "couple with heart: woman, woman",
                "tone": true
            },
            {
                "emoji": "👨‍👩‍👦",
                "title": "family: man, woman, boy"
            },
            {
                "emoji": "👨‍👩‍👧",
                "title": "family: man, woman, girl"
            },
            {
                "emoji": "👨‍👩‍👧‍👦",
                "title": "family: man, woman, girl, boy"
            },
            {
                "emoji": "👨‍👩‍👦‍👦",
                "title": "family: man, woman, boy, boy"
            },
            {
                "emoji": "👨‍👩‍👧‍👧",
                "title": "family: man, woman, girl, girl"
            },
            {
                "emoji": "👨‍👨‍👦",
                "title": "family: man, man, boy"
            },
            {
                "emoji": "👨‍👨‍👧",
                "title": "family: man, man, girl"
            },
            {
                "emoji": "👨‍👨‍👧‍👦",
                "title": "family: man, man, girl, boy"
            },
            {
                "emoji": "👨‍👨‍👦‍👦",
                "title": "family: man, man, boy, boy"
            },
            {
                "emoji": "👨‍👨‍👧‍👧",
                "title": "family: man, man, girl, girl"
            },
            {
                "emoji": "👩‍👩‍👦",
                "title": "family: woman, woman, boy"
            },
            {
                "emoji": "👩‍👩‍👧",
                "title": "family: woman, woman, girl"
            },
            {
                "emoji": "👩‍👩‍👧‍👦",
                "title": "family: woman, woman, girl, boy"
            },
            {
                "emoji": "👩‍👩‍👦‍👦",
                "title": "family: woman, woman, boy, boy"
            },
            {
                "emoji": "👩‍👩‍👧‍👧",
                "title": "family: woman, woman, girl, girl"
            },
            {
                "emoji": "👨‍👦",
                "title": "family: man, boy"
            },
            {
                "emoji": "👨‍👦‍👦",
                "title": "family: man, boy, boy"
            },
            {
                "emoji": "👨‍👧",
                "title": "family: man, girl"
            },
            {
                "emoji": "👨‍👧‍👦",
                "title": "family: man, girl, boy"
            },
            {
                "emoji": "👨‍👧‍👧",
                "title": "family: man, girl, girl"
            },
            {
                "emoji": "👩‍👦",
                "title": "family: woman, boy"
            },
            {
                "emoji": "👩‍👦‍👦",
                "title": "family: woman, boy, boy"
            },
            {
                "emoji": "👩‍👧",
                "title": "family: woman, girl"
            },
            {
                "emoji": "👩‍👧‍👦",
                "title": "family: woman, girl, boy"
            },
            {
                "emoji": "👩‍👧‍👧",
                "title": "family: woman, girl, girl"
            },
            {
                "emoji": "🗣️",
                "title": "speaking head"
            },
            {
                "emoji": "👤",
                "title": "bust in silhouette"
            },
            {
                "emoji": "👥",
                "title": "busts in silhouette"
            },
            {
                "emoji": "🫂",
                "title": "people hugging"
            },
            {
                "emoji": "👪",
                "title": "family"
            },
            {
                "emoji": "🧑‍🧑‍🧒",
                "title": "family: adult, adult, child"
            },
            {
                "emoji": "🧑‍🧑‍🧒‍🧒",
                "title": "family: adult, adult, child, child"
            },
            {
                "emoji": "🧑‍🧒",
                "title": "family: adult, child"
            },
            {
                "emoji": "🧑‍🧒‍🧒",
                "title": "family: adult, child, child"
            },
            {
                "emoji": "👣",
                "title": "footprints"
            },
            {
                "emoji": "🫆",
                "title": "fingerprint"
            }
        ],
        'Nature': [
            {
                "emoji": "🐵",
                "title": "monkey face"
            },
            {
                "emoji": "🐒",
                "title": "monkey"
            },
            {
                "emoji": "🦍",
                "title": "gorilla"
            },
            {
                "emoji": "🦧",
                "title": "orangutan"
            },
            {
                "emoji": "🐶",
                "title": "dog face"
            },
            {
                "emoji": "🐕",
                "title": "dog"
            },
            {
                "emoji": "🦮",
                "title": "guide dog"
            },
            {
                "emoji": "🐕‍🦺",
                "title": "service dog"
            },
            {
                "emoji": "🐩",
                "title": "poodle"
            },
            {
                "emoji": "🐺",
                "title": "wolf"
            },
            {
                "emoji": "🦊",
                "title": "fox"
            },
            {
                "emoji": "🦝",
                "title": "raccoon"
            },
            {
                "emoji": "🐱",
                "title": "cat face"
            },
            {
                "emoji": "🐈",
                "title": "cat"
            },
            {
                "emoji": "🐈‍⬛",
                "title": "black cat"
            },
            {
                "emoji": "🦁",
                "title": "lion"
            },
            {
                "emoji": "🐯",
                "title": "tiger face"
            },
            {
                "emoji": "🐅",
                "title": "tiger"
            },
            {
                "emoji": "🐆",
                "title": "leopard"
            },
            {
                "emoji": "🐴",
                "title": "horse face"
            },
            {
                "emoji": "🫎",
                "title": "moose"
            },
            {
                "emoji": "🫏",
                "title": "donkey"
            },
            {
                "emoji": "🐎",
                "title": "horse"
            },
            {
                "emoji": "🦄",
                "title": "unicorn"
            },
            {
                "emoji": "🦓",
                "title": "zebra"
            },
            {
                "emoji": "🦌",
                "title": "deer"
            },
            {
                "emoji": "🦬",
                "title": "bison"
            },
            {
                "emoji": "🐮",
                "title": "cow face"
            },
            {
                "emoji": "🐂",
                "title": "ox"
            },
            {
                "emoji": "🐃",
                "title": "water buffalo"
            },
            {
                "emoji": "🐄",
                "title": "cow"
            },
            {
                "emoji": "🐷",
                "title": "pig face"
            },
            {
                "emoji": "🐖",
                "title": "pig"
            },
            {
                "emoji": "🐗",
                "title": "boar"
            },
            {
                "emoji": "🐽",
                "title": "pig nose"
            },
            {
                "emoji": "🐏",
                "title": "ram"
            },
            {
                "emoji": "🐑",
                "title": "ewe"
            },
            {
                "emoji": "🐐",
                "title": "goat"
            },
            {
                "emoji": "🐪",
                "title": "camel"
            },
            {
                "emoji": "🐫",
                "title": "two-hump camel"
            },
            {
                "emoji": "🦙",
                "title": "llama"
            },
            {
                "emoji": "🦒",
                "title": "giraffe"
            },
            {
                "emoji": "🐘",
                "title": "elephant"
            },
            {
                "emoji": "🦣",
                "title": "mammoth"
            },
            {
                "emoji": "🦏",
                "title": "rhinoceros"
            },
            {
                "emoji": "🦛",
                "title": "hippopotamus"
            },
            {
                "emoji": "🐭",
                "title": "mouse face"
            },
            {
                "emoji": "🐁",
                "title": "mouse"
            },
            {
                "emoji": "🐀",
                "title": "rat"
            },
            {
                "emoji": "🐹",
                "title": "hamster"
            },
            {
                "emoji": "🐰",
                "title": "rabbit face"
            },
            {
                "emoji": "🐇",
                "title": "rabbit"
            },
            {
                "emoji": "🐿️",
                "title": "chipmunk"
            },
            {
                "emoji": "🦫",
                "title": "beaver"
            },
            {
                "emoji": "🦔",
                "title": "hedgehog"
            },
            {
                "emoji": "🦇",
                "title": "bat"
            },
            {
                "emoji": "🐻",
                "title": "bear"
            },
            {
                "emoji": "🐻‍❄️",
                "title": "polar bear"
            },
            {
                "emoji": "🐨",
                "title": "koala"
            },
            {
                "emoji": "🐼",
                "title": "panda"
            },
            {
                "emoji": "🦥",
                "title": "sloth"
            },
            {
                "emoji": "🦦",
                "title": "otter"
            },
            {
                "emoji": "🦨",
                "title": "skunk"
            },
            {
                "emoji": "🦘",
                "title": "kangaroo"
            },
            {
                "emoji": "🦡",
                "title": "badger"
            },
            {
                "emoji": "🐾",
                "title": "paw prints"
            },
            {
                "emoji": "🦃",
                "title": "turkey"
            },
            {
                "emoji": "🐔",
                "title": "chicken"
            },
            {
                "emoji": "🐓",
                "title": "rooster"
            },
            {
                "emoji": "🐣",
                "title": "hatching chick"
            },
            {
                "emoji": "🐤",
                "title": "baby chick"
            },
            {
                "emoji": "🐥",
                "title": "front-facing baby chick"
            },
            {
                "emoji": "🐦",
                "title": "bird"
            },
            {
                "emoji": "🐧",
                "title": "penguin"
            },
            {
                "emoji": "🕊️",
                "title": "dove"
            },
            {
                "emoji": "🦅",
                "title": "eagle"
            },
            {
                "emoji": "🦆",
                "title": "duck"
            },
            {
                "emoji": "🦢",
                "title": "swan"
            },
            {
                "emoji": "🦉",
                "title": "owl"
            },
            {
                "emoji": "🦤",
                "title": "dodo"
            },
            {
                "emoji": "🪶",
                "title": "feather"
            },
            {
                "emoji": "🦩",
                "title": "flamingo"
            },
            {
                "emoji": "🦚",
                "title": "peacock"
            },
            {
                "emoji": "🦜",
                "title": "parrot"
            },
            {
                "emoji": "🪽",
                "title": "wing"
            },
            {
                "emoji": "🐦‍⬛",
                "title": "black bird"
            },
            {
                "emoji": "🪿",
                "title": "goose"
            },
            {
                "emoji": "🐦‍🔥",
                "title": "phoenix"
            },
            {
                "emoji": "🐸",
                "title": "frog"
            },
            {
                "emoji": "🐊",
                "title": "crocodile"
            },
            {
                "emoji": "🐢",
                "title": "turtle"
            },
            {
                "emoji": "🦎",
                "title": "lizard"
            },
            {
                "emoji": "🐍",
                "title": "snake"
            },
            {
                "emoji": "🐲",
                "title": "dragon face"
            },
            {
                "emoji": "🐉",
                "title": "dragon"
            },
            {
                "emoji": "🦕",
                "title": "sauropod"
            },
            {
                "emoji": "🦖",
                "title": "T-Rex"
            },
            {
                "emoji": "🐳",
                "title": "spouting whale"
            },
            {
                "emoji": "🐋",
                "title": "whale"
            },
            {
                "emoji": "🐬",
                "title": "dolphin"
            },
            {
                "emoji": "🫍",
                "v": 17,
                "title": "orca"
            },
            {
                "emoji": "🦭",
                "title": "seal"
            },
            {
                "emoji": "🐟",
                "title": "fish"
            },
            {
                "emoji": "🐠",
                "title": "tropical fish"
            },
            {
                "emoji": "🐡",
                "title": "blowfish"
            },
            {
                "emoji": "🦈",
                "title": "shark"
            },
            {
                "emoji": "🐙",
                "title": "octopus"
            },
            {
                "emoji": "🐚",
                "title": "spiral shell"
            },
            {
                "emoji": "🪸",
                "title": "coral"
            },
            {
                "emoji": "🪼",
                "title": "jellyfish"
            },
            {
                "emoji": "🦀",
                "title": "crab"
            },
            {
                "emoji": "🦞",
                "title": "lobster"
            },
            {
                "emoji": "🦐",
                "title": "shrimp"
            },
            {
                "emoji": "🦑",
                "title": "squid"
            },
            {
                "emoji": "🦪",
                "title": "oyster"
            },
            {
                "emoji": "🐌",
                "title": "snail"
            },
            {
                "emoji": "🦋",
                "title": "butterfly"
            },
            {
                "emoji": "🫌",
                "v": 18,
                "title": "monarch butterfly"
            },
            {
                "emoji": "🐛",
                "title": "bug"
            },
            {
                "emoji": "🐜",
                "title": "ant"
            },
            {
                "emoji": "🐝",
                "title": "honeybee"
            },
            {
                "emoji": "🪲",
                "title": "beetle"
            },
            {
                "emoji": "🐞",
                "title": "lady beetle"
            },
            {
                "emoji": "🦗",
                "title": "cricket"
            },
            {
                "emoji": "🪳",
                "title": "cockroach"
            },
            {
                "emoji": "🕷️",
                "title": "spider"
            },
            {
                "emoji": "🕸️",
                "title": "spider web"
            },
            {
                "emoji": "🦂",
                "title": "scorpion"
            },
            {
                "emoji": "🦟",
                "title": "mosquito"
            },
            {
                "emoji": "🪰",
                "title": "fly"
            },
            {
                "emoji": "🪱",
                "title": "worm"
            },
            {
                "emoji": "🦠",
                "title": "microbe"
            },
            {
                "emoji": "💐",
                "title": "bouquet"
            },
            {
                "emoji": "🌸",
                "title": "cherry blossom"
            },
            {
                "emoji": "💮",
                "title": "white flower"
            },
            {
                "emoji": "🪷",
                "title": "lotus"
            },
            {
                "emoji": "🏵️",
                "title": "rosette"
            },
            {
                "emoji": "🌹",
                "title": "rose"
            },
            {
                "emoji": "🥀",
                "title": "wilted flower"
            },
            {
                "emoji": "🌺",
                "title": "hibiscus"
            },
            {
                "emoji": "🌻",
                "title": "sunflower"
            },
            {
                "emoji": "🌼",
                "title": "blossom"
            },
            {
                "emoji": "🌷",
                "title": "tulip"
            },
            {
                "emoji": "🪻",
                "title": "hyacinth"
            },
            {
                "emoji": "🌱",
                "title": "seedling"
            },
            {
                "emoji": "🪴",
                "title": "potted plant"
            },
            {
                "emoji": "🌲",
                "title": "evergreen tree"
            },
            {
                "emoji": "🌳",
                "title": "deciduous tree"
            },
            {
                "emoji": "🌴",
                "title": "palm tree"
            },
            {
                "emoji": "🌵",
                "title": "cactus"
            },
            {
                "emoji": "🌾",
                "title": "sheaf of rice"
            },
            {
                "emoji": "🌿",
                "title": "herb"
            },
            {
                "emoji": "☘️",
                "title": "shamrock"
            },
            {
                "emoji": "🍀",
                "title": "four leaf clover"
            },
            {
                "emoji": "🍁",
                "title": "maple leaf"
            },
            {
                "emoji": "🍂",
                "title": "fallen leaf"
            },
            {
                "emoji": "🍃",
                "title": "leaf fluttering in wind"
            },
            {
                "emoji": "🪹",
                "title": "empty nest"
            },
            {
                "emoji": "🪺",
                "title": "nest with eggs"
            },
            {
                "emoji": "🍄",
                "title": "mushroom"
            },
            {
                "emoji": "🪾",
                "title": "leafless tree"
            }
        ],
        'Food-dring': [
            {
                "emoji": "🍇",
                "title": "grapes"
            },
            {
                "emoji": "🍈",
                "title": "melon"
            },
            {
                "emoji": "🍉",
                "title": "watermelon"
            },
            {
                "emoji": "🍊",
                "title": "tangerine"
            },
            {
                "emoji": "🍋",
                "title": "lemon"
            },
            {
                "emoji": "🍋‍🟩",
                "title": "lime"
            },
            {
                "emoji": "🍌",
                "title": "banana"
            },
            {
                "emoji": "🍍",
                "title": "pineapple"
            },
            {
                "emoji": "🥭",
                "title": "mango"
            },
            {
                "emoji": "🍎",
                "title": "red apple"
            },
            {
                "emoji": "🍏",
                "title": "green apple"
            },
            {
                "emoji": "🍐",
                "title": "pear"
            },
            {
                "emoji": "🍑",
                "title": "peach"
            },
            {
                "emoji": "🍒",
                "title": "cherries"
            },
            {
                "emoji": "🍓",
                "title": "strawberry"
            },
            {
                "emoji": "🫐",
                "title": "blueberries"
            },
            {
                "emoji": "🥝",
                "title": "kiwi fruit"
            },
            {
                "emoji": "🍅",
                "title": "tomato"
            },
            {
                "emoji": "🫒",
                "title": "olive"
            },
            {
                "emoji": "🥥",
                "title": "coconut"
            },
            {
                "emoji": "🥑",
                "title": "avocado"
            },
            {
                "emoji": "🍆",
                "title": "eggplant"
            },
            {
                "emoji": "🥔",
                "title": "potato"
            },
            {
                "emoji": "🥕",
                "title": "carrot"
            },
            {
                "emoji": "🌽",
                "title": "ear of corn"
            },
            {
                "emoji": "🌶️",
                "title": "hot pepper"
            },
            {
                "emoji": "🫑",
                "title": "bell pepper"
            },
            {
                "emoji": "🥒",
                "title": "cucumber"
            },
            {
                "emoji": "🫝",
                "v": 18,
                "title": "pickle"
            },
            {
                "emoji": "🥬",
                "title": "leafy green"
            },
            {
                "emoji": "🥦",
                "title": "broccoli"
            },
            {
                "emoji": "🧄",
                "title": "garlic"
            },
            {
                "emoji": "🧅",
                "title": "onion"
            },
            {
                "emoji": "🥜",
                "title": "peanuts"
            },
            {
                "emoji": "🫘",
                "title": "beans"
            },
            {
                "emoji": "🌰",
                "title": "chestnut"
            },
            {
                "emoji": "🫚",
                "title": "ginger root"
            },
            {
                "emoji": "🫛",
                "title": "pea pod"
            },
            {
                "emoji": "🍄‍🟫",
                "title": "brown mushroom"
            },
            {
                "emoji": "🫜",
                "title": "root vegetable"
            },
            {
                "emoji": "🍞",
                "title": "bread"
            },
            {
                "emoji": "🥐",
                "title": "croissant"
            },
            {
                "emoji": "🥖",
                "title": "baguette bread"
            },
            {
                "emoji": "🫓",
                "title": "flatbread"
            },
            {
                "emoji": "🥨",
                "title": "pretzel"
            },
            {
                "emoji": "🥯",
                "title": "bagel"
            },
            {
                "emoji": "🥞",
                "title": "pancakes"
            },
            {
                "emoji": "🧇",
                "title": "waffle"
            },
            {
                "emoji": "🧀",
                "title": "cheese wedge"
            },
            {
                "emoji": "🍖",
                "title": "meat on bone"
            },
            {
                "emoji": "🍗",
                "title": "poultry leg"
            },
            {
                "emoji": "🥩",
                "title": "cut of meat"
            },
            {
                "emoji": "🥓",
                "title": "bacon"
            },
            {
                "emoji": "🍔",
                "title": "hamburger"
            },
            {
                "emoji": "🍟",
                "title": "french fries"
            },
            {
                "emoji": "🍕",
                "title": "pizza"
            },
            {
                "emoji": "🌭",
                "title": "hot dog"
            },
            {
                "emoji": "🥪",
                "title": "sandwich"
            },
            {
                "emoji": "🌮",
                "title": "taco"
            },
            {
                "emoji": "🌯",
                "title": "burrito"
            },
            {
                "emoji": "🫔",
                "title": "tamale"
            },
            {
                "emoji": "🥙",
                "title": "stuffed flatbread"
            },
            {
                "emoji": "🧆",
                "title": "falafel"
            },
            {
                "emoji": "🥚",
                "title": "egg"
            },
            {
                "emoji": "🍳",
                "title": "cooking"
            },
            {
                "emoji": "🥘",
                "title": "shallow pan of food"
            },
            {
                "emoji": "🍲",
                "title": "pot of food"
            },
            {
                "emoji": "🫕",
                "title": "fondue"
            },
            {
                "emoji": "🥣",
                "title": "bowl with spoon"
            },
            {
                "emoji": "🥗",
                "title": "green salad"
            },
            {
                "emoji": "🍿",
                "title": "popcorn"
            },
            {
                "emoji": "🧈",
                "title": "butter"
            },
            {
                "emoji": "🧂",
                "title": "salt"
            },
            {
                "emoji": "🥫",
                "title": "canned food"
            },
            {
                "emoji": "🍱",
                "title": "bento box"
            },
            {
                "emoji": "🍘",
                "title": "rice cracker"
            },
            {
                "emoji": "🍙",
                "title": "rice ball"
            },
            {
                "emoji": "🍚",
                "title": "cooked rice"
            },
            {
                "emoji": "🍛",
                "title": "curry rice"
            },
            {
                "emoji": "🍜",
                "title": "steaming bowl"
            },
            {
                "emoji": "🍝",
                "title": "spaghetti"
            },
            {
                "emoji": "🍠",
                "title": "roasted sweet potato"
            },
            {
                "emoji": "🍢",
                "title": "oden"
            },
            {
                "emoji": "🍣",
                "title": "sushi"
            },
            {
                "emoji": "🍤",
                "title": "fried shrimp"
            },
            {
                "emoji": "🍥",
                "title": "fish cake with swirl"
            },
            {
                "emoji": "🥮",
                "title": "moon cake"
            },
            {
                "emoji": "🍡",
                "title": "dango"
            },
            {
                "emoji": "🥟",
                "title": "dumpling"
            },
            {
                "emoji": "🥠",
                "title": "fortune cookie"
            },
            {
                "emoji": "🥡",
                "title": "takeout box"
            },
            {
                "emoji": "🍦",
                "title": "soft ice cream"
            },
            {
                "emoji": "🍧",
                "title": "shaved ice"
            },
            {
                "emoji": "🍨",
                "title": "ice cream"
            },
            {
                "emoji": "🍩",
                "title": "doughnut"
            },
            {
                "emoji": "🍪",
                "title": "cookie"
            },
            {
                "emoji": "🎂",
                "title": "birthday cake"
            },
            {
                "emoji": "🍰",
                "title": "shortcake"
            },
            {
                "emoji": "🧁",
                "title": "cupcake"
            },
            {
                "emoji": "🥧",
                "title": "pie"
            },
            {
                "emoji": "🍫",
                "title": "chocolate bar"
            },
            {
                "emoji": "🍬",
                "title": "candy"
            },
            {
                "emoji": "🍭",
                "title": "lollipop"
            },
            {
                "emoji": "🍮",
                "title": "custard"
            },
            {
                "emoji": "🍯",
                "title": "honey pot"
            },
            {
                "emoji": "🍼",
                "title": "baby bottle"
            },
            {
                "emoji": "🥛",
                "title": "glass of milk"
            },
            {
                "emoji": "☕",
                "title": "hot beverage"
            },
            {
                "emoji": "🫖",
                "title": "teapot"
            },
            {
                "emoji": "🍵",
                "title": "teacup without handle"
            },
            {
                "emoji": "🍶",
                "title": "sake"
            },
            {
                "emoji": "🍾",
                "title": "bottle with popping cork"
            },
            {
                "emoji": "🍷",
                "title": "wine glass"
            },
            {
                "emoji": "🍸",
                "title": "cocktail glass"
            },
            {
                "emoji": "🍹",
                "title": "tropical drink"
            },
            {
                "emoji": "🍺",
                "title": "beer mug"
            },
            {
                "emoji": "🍻",
                "title": "clinking beer mugs"
            },
            {
                "emoji": "🥂",
                "title": "clinking glasses"
            },
            {
                "emoji": "🥃",
                "title": "tumbler glass"
            },
            {
                "emoji": "🫗",
                "title": "pouring liquid"
            },
            {
                "emoji": "🥤",
                "title": "cup with straw"
            },
            {
                "emoji": "🧋",
                "title": "bubble tea"
            },
            {
                "emoji": "🧃",
                "title": "beverage box"
            },
            {
                "emoji": "🧉",
                "title": "mate"
            },
            {
                "emoji": "🧊",
                "title": "ice"
            },
            {
                "emoji": "🥢",
                "title": "chopsticks"
            },
            {
                "emoji": "🍽️",
                "title": "fork and knife with plate"
            },
            {
                "emoji": "🍴",
                "title": "fork and knife"
            },
            {
                "emoji": "🥄",
                "title": "spoon"
            },
            {
                "emoji": "🔪",
                "title": "kitchen knife"
            },
            {
                "emoji": "🫙",
                "title": "jar"
            },
            {
                "emoji": "🏺",
                "title": "amphora"
            }
        ],
        'Activity': [
            {
                "emoji": "🎃",
                "title": "jack-o-lantern"
            },
            {
                "emoji": "🎄",
                "title": "Christmas tree"
            },
            {
                "emoji": "🎆",
                "title": "fireworks"
            },
            {
                "emoji": "🎇",
                "title": "sparkler"
            },
            {
                "emoji": "🧨",
                "title": "firecracker"
            },
            {
                "emoji": "✨",
                "title": "sparkles"
            },
            {
                "emoji": "🎈",
                "title": "balloon"
            },
            {
                "emoji": "🎉",
                "title": "party popper"
            },
            {
                "emoji": "🎊",
                "title": "confetti ball"
            },
            {
                "emoji": "🎋",
                "title": "tanabata tree"
            },
            {
                "emoji": "🎍",
                "title": "pine decoration"
            },
            {
                "emoji": "🎎",
                "title": "Japanese dolls"
            },
            {
                "emoji": "🎏",
                "title": "carp streamer"
            },
            {
                "emoji": "🎐",
                "title": "wind chime"
            },
            {
                "emoji": "🎑",
                "title": "moon viewing ceremony"
            },
            {
                "emoji": "🧧",
                "title": "red envelope"
            },
            {
                "emoji": "🎀",
                "title": "ribbon"
            },
            {
                "emoji": "🎁",
                "title": "wrapped gift"
            },
            {
                "emoji": "🎗️",
                "title": "reminder ribbon"
            },
            {
                "emoji": "🎟️",
                "title": "admission tickets"
            },
            {
                "emoji": "🎫",
                "title": "ticket"
            },
            {
                "emoji": "🎖️",
                "title": "military medal"
            },
            {
                "emoji": "🏆",
                "title": "trophy"
            },
            {
                "emoji": "🏅",
                "title": "sports medal"
            },
            {
                "emoji": "🥇",
                "title": "1st place medal"
            },
            {
                "emoji": "🥈",
                "title": "2nd place medal"
            },
            {
                "emoji": "🥉",
                "title": "3rd place medal"
            },
            {
                "emoji": "⚽",
                "title": "soccer ball"
            },
            {
                "emoji": "⚾",
                "title": "baseball"
            },
            {
                "emoji": "🥎",
                "title": "softball"
            },
            {
                "emoji": "🏀",
                "title": "basketball"
            },
            {
                "emoji": "🏐",
                "title": "volleyball"
            },
            {
                "emoji": "🏈",
                "title": "american football"
            },
            {
                "emoji": "🏉",
                "title": "rugby football"
            },
            {
                "emoji": "🎾",
                "title": "tennis"
            },
            {
                "emoji": "🥏",
                "title": "flying disc"
            },
            {
                "emoji": "🎳",
                "title": "bowling"
            },
            {
                "emoji": "🏏",
                "title": "cricket game"
            },
            {
                "emoji": "🏑",
                "title": "field hockey"
            },
            {
                "emoji": "🏒",
                "title": "ice hockey"
            },
            {
                "emoji": "🥍",
                "title": "lacrosse"
            },
            {
                "emoji": "🏓",
                "title": "ping pong"
            },
            {
                "emoji": "🏸",
                "title": "badminton"
            },
            {
                "emoji": "🥊",
                "title": "boxing glove"
            },
            {
                "emoji": "🥋",
                "title": "martial arts uniform"
            },
            {
                "emoji": "🥅",
                "title": "goal net"
            },
            {
                "emoji": "⛳",
                "title": "flag in hole"
            },
            {
                "emoji": "⛸️",
                "title": "ice skate"
            },
            {
                "emoji": "🎣",
                "title": "fishing pole"
            },
            {
                "emoji": "🤿",
                "title": "diving mask"
            },
            {
                "emoji": "🎽",
                "title": "running shirt"
            },
            {
                "emoji": "🎿",
                "title": "skis"
            },
            {
                "emoji": "🛷",
                "title": "sled"
            },
            {
                "emoji": "🥌",
                "title": "curling stone"
            },
            {
                "emoji": "🎯",
                "title": "bullseye"
            },
            {
                "emoji": "🪀",
                "title": "yo-yo"
            },
            {
                "emoji": "🪁",
                "title": "kite"
            },
            {
                "emoji": "🔫",
                "title": "water pistol"
            },
            {
                "emoji": "🎱",
                "title": "pool 8 ball"
            },
            {
                "emoji": "🔮",
                "title": "crystal ball"
            },
            {
                "emoji": "🪄",
                "title": "magic wand"
            },
            {
                "emoji": "🎮",
                "title": "video game"
            },
            {
                "emoji": "🕹️",
                "title": "joystick"
            },
            {
                "emoji": "🎰",
                "title": "slot machine"
            },
            {
                "emoji": "🎲",
                "title": "game die"
            },
            {
                "emoji": "🧩",
                "title": "puzzle piece"
            },
            {
                "emoji": "🧸",
                "title": "teddy bear"
            },
            {
                "emoji": "🪅",
                "title": "piñata"
            },
            {
                "emoji": "🪩",
                "title": "mirror ball"
            },
            {
                "emoji": "🪆",
                "title": "nesting dolls"
            },
            {
                "emoji": "♠️",
                "title": "spade suit"
            },
            {
                "emoji": "♥️",
                "title": "heart suit"
            },
            {
                "emoji": "♦️",
                "title": "diamond suit"
            },
            {
                "emoji": "♣️",
                "title": "club suit"
            },
            {
                "emoji": "♟️",
                "title": "chess pawn"
            },
            {
                "emoji": "🃏",
                "title": "joker"
            },
            {
                "emoji": "🀄",
                "title": "mahjong red dragon"
            },
            {
                "emoji": "🎴",
                "title": "flower playing cards"
            },
            {
                "emoji": "🎭",
                "title": "performing arts"
            },
            {
                "emoji": "🖼️",
                "title": "framed picture"
            },
            {
                "emoji": "🎨",
                "title": "artist palette"
            },
            {
                "emoji": "🧵",
                "title": "thread"
            },
            {
                "emoji": "🪡",
                "title": "sewing needle"
            },
            {
                "emoji": "🧶",
                "title": "yarn"
            },
            {
                "emoji": "🪢",
                "title": "knot"
            }
        ],
        'Travel-places': [
            {
                "emoji": "🌍",
                "title": "globe showing Europe-Africa"
            },
            {
                "emoji": "🌎",
                "title": "globe showing Americas"
            },
            {
                "emoji": "🌏",
                "title": "globe showing Asia-Australia"
            },
            {
                "emoji": "🌐",
                "title": "globe with meridians"
            },
            {
                "emoji": "🗺️",
                "title": "world map"
            },
            {
                "emoji": "🗾",
                "title": "map of Japan"
            },
            {
                "emoji": "🧭",
                "title": "compass"
            },
            {
                "emoji": "🏔️",
                "title": "snow-capped mountain"
            },
            {
                "emoji": "⛰️",
                "title": "mountain"
            },
            {
                "emoji": "🛘",
                "v": 17,
                "title": "landslide"
            },
            {
                "emoji": "🌋",
                "title": "volcano"
            },
            {
                "emoji": "🗻",
                "title": "mount fuji"
            },
            {
                "emoji": "🏕️",
                "title": "camping"
            },
            {
                "emoji": "🏖️",
                "title": "beach with umbrella"
            },
            {
                "emoji": "🏜️",
                "title": "desert"
            },
            {
                "emoji": "🏝️",
                "title": "desert island"
            },
            {
                "emoji": "🏞️",
                "title": "national park"
            },
            {
                "emoji": "🏟️",
                "title": "stadium"
            },
            {
                "emoji": "🏛️",
                "title": "classical building"
            },
            {
                "emoji": "🏗️",
                "title": "building construction"
            },
            {
                "emoji": "🧱",
                "title": "brick"
            },
            {
                "emoji": "🪨",
                "title": "rock"
            },
            {
                "emoji": "🪵",
                "title": "wood"
            },
            {
                "emoji": "🛖",
                "title": "hut"
            },
            {
                "emoji": "🏘️",
                "title": "houses"
            },
            {
                "emoji": "🏚️",
                "title": "derelict house"
            },
            {
                "emoji": "🏠",
                "title": "house"
            },
            {
                "emoji": "🏡",
                "title": "house with garden"
            },
            {
                "emoji": "🏢",
                "title": "office building"
            },
            {
                "emoji": "🏣",
                "title": "Japanese post office"
            },
            {
                "emoji": "🏤",
                "title": "post office"
            },
            {
                "emoji": "🏥",
                "title": "hospital"
            },
            {
                "emoji": "🏦",
                "title": "bank"
            },
            {
                "emoji": "🏨",
                "title": "hotel"
            },
            {
                "emoji": "🏩",
                "title": "love hotel"
            },
            {
                "emoji": "🏪",
                "title": "convenience store"
            },
            {
                "emoji": "🏫",
                "title": "school"
            },
            {
                "emoji": "🏬",
                "title": "department store"
            },
            {
                "emoji": "🏭",
                "title": "factory"
            },
            {
                "emoji": "🏯",
                "title": "Japanese castle"
            },
            {
                "emoji": "🏰",
                "title": "castle"
            },
            {
                "emoji": "💒",
                "title": "wedding"
            },
            {
                "emoji": "🗼",
                "title": "Tokyo tower"
            },
            {
                "emoji": "🗽",
                "title": "Statue of Liberty"
            },
            {
                "emoji": "⛪",
                "title": "church"
            },
            {
                "emoji": "🕌",
                "title": "mosque"
            },
            {
                "emoji": "🛕",
                "title": "hindu temple"
            },
            {
                "emoji": "🕍",
                "title": "synagogue"
            },
            {
                "emoji": "⛩️",
                "title": "shinto shrine"
            },
            {
                "emoji": "🕋",
                "title": "kaaba"
            },
            {
                "emoji": "⛲",
                "title": "fountain"
            },
            {
                "emoji": "⛺",
                "title": "tent"
            },
            {
                "emoji": "🌁",
                "title": "foggy"
            },
            {
                "emoji": "🌃",
                "title": "night with stars"
            },
            {
                "emoji": "🏙️",
                "title": "cityscape"
            },
            {
                "emoji": "🌄",
                "title": "sunrise over mountains"
            },
            {
                "emoji": "🌅",
                "title": "sunrise"
            },
            {
                "emoji": "🌆",
                "title": "cityscape at dusk"
            },
            {
                "emoji": "🌇",
                "title": "sunset"
            },
            {
                "emoji": "🌉",
                "title": "bridge at night"
            },
            {
                "emoji": "♨️",
                "title": "hot springs"
            },
            {
                "emoji": "🎠",
                "title": "carousel horse"
            },
            {
                "emoji": "🛝",
                "title": "playground slide"
            },
            {
                "emoji": "🎡",
                "title": "ferris wheel"
            },
            {
                "emoji": "🎢",
                "title": "roller coaster"
            },
            {
                "emoji": "💈",
                "title": "barber pole"
            },
            {
                "emoji": "🎪",
                "title": "circus tent"
            },
            {
                "emoji": "🚂",
                "title": "locomotive"
            },
            {
                "emoji": "🚃",
                "title": "railway car"
            },
            {
                "emoji": "🚄",
                "title": "high-speed train"
            },
            {
                "emoji": "🚅",
                "title": "bullet train"
            },
            {
                "emoji": "🚆",
                "title": "train"
            },
            {
                "emoji": "🚇",
                "title": "metro"
            },
            {
                "emoji": "🚈",
                "title": "light rail"
            },
            {
                "emoji": "🚉",
                "title": "station"
            },
            {
                "emoji": "🚊",
                "title": "tram"
            },
            {
                "emoji": "🚝",
                "title": "monorail"
            },
            {
                "emoji": "🚞",
                "title": "mountain railway"
            },
            {
                "emoji": "🚋",
                "title": "tram car"
            },
            {
                "emoji": "🚌",
                "title": "bus"
            },
            {
                "emoji": "🚍",
                "title": "oncoming bus"
            },
            {
                "emoji": "🚎",
                "title": "trolleybus"
            },
            {
                "emoji": "🚐",
                "title": "minibus"
            },
            {
                "emoji": "🚑",
                "title": "ambulance"
            },
            {
                "emoji": "🚒",
                "title": "fire engine"
            },
            {
                "emoji": "🚓",
                "title": "police car"
            },
            {
                "emoji": "🚔",
                "title": "oncoming police car"
            },
            {
                "emoji": "🚕",
                "title": "taxi"
            },
            {
                "emoji": "🚖",
                "title": "oncoming taxi"
            },
            {
                "emoji": "🚗",
                "title": "automobile"
            },
            {
                "emoji": "🚘",
                "title": "oncoming automobile"
            },
            {
                "emoji": "🚙",
                "title": "sport utility vehicle"
            },
            {
                "emoji": "🛻",
                "title": "pickup truck"
            },
            {
                "emoji": "🚚",
                "title": "delivery truck"
            },
            {
                "emoji": "🚛",
                "title": "articulated lorry"
            },
            {
                "emoji": "🚜",
                "title": "tractor"
            },
            {
                "emoji": "🏎️",
                "title": "racing car"
            },
            {
                "emoji": "🏍️",
                "title": "motorcycle"
            },
            {
                "emoji": "🛵",
                "title": "motor scooter"
            },
            {
                "emoji": "🦽",
                "title": "manual wheelchair"
            },
            {
                "emoji": "🦼",
                "title": "motorized wheelchair"
            },
            {
                "emoji": "🛺",
                "title": "auto rickshaw"
            },
            {
                "emoji": "🚲",
                "title": "bicycle"
            },
            {
                "emoji": "🛴",
                "title": "kick scooter"
            },
            {
                "emoji": "🛹",
                "title": "skateboard"
            },
            {
                "emoji": "🛼",
                "title": "roller skate"
            },
            {
                "emoji": "🚏",
                "title": "bus stop"
            },
            {
                "emoji": "🛣️",
                "title": "motorway"
            },
            {
                "emoji": "🛤️",
                "title": "railway track"
            },
            {
                "emoji": "🛢️",
                "title": "oil drum"
            },
            {
                "emoji": "⛽",
                "title": "fuel pump"
            },
            {
                "emoji": "🛞",
                "title": "wheel"
            },
            {
                "emoji": "🚨",
                "title": "police car light"
            },
            {
                "emoji": "🚥",
                "title": "horizontal traffic light"
            },
            {
                "emoji": "🚦",
                "title": "vertical traffic light"
            },
            {
                "emoji": "🛑",
                "title": "stop sign"
            },
            {
                "emoji": "🚧",
                "title": "construction"
            },
            {
                "emoji": "🛙",
                "v": 18,
                "title": "lighthouse"
            },
            {
                "emoji": "⚓",
                "title": "anchor"
            },
            {
                "emoji": "🛟",
                "title": "ring buoy"
            },
            {
                "emoji": "⛵",
                "title": "sailboat"
            },
            {
                "emoji": "🛶",
                "title": "canoe"
            },
            {
                "emoji": "🚤",
                "title": "speedboat"
            },
            {
                "emoji": "🛳️",
                "title": "passenger ship"
            },
            {
                "emoji": "⛴️",
                "title": "ferry"
            },
            {
                "emoji": "🛥️",
                "title": "motor boat"
            },
            {
                "emoji": "🚢",
                "title": "ship"
            },
            {
                "emoji": "✈️",
                "title": "airplane"
            },
            {
                "emoji": "🛩️",
                "title": "small airplane"
            },
            {
                "emoji": "🛫",
                "title": "airplane departure"
            },
            {
                "emoji": "🛬",
                "title": "airplane arrival"
            },
            {
                "emoji": "🪂",
                "title": "parachute"
            },
            {
                "emoji": "💺",
                "title": "seat"
            },
            {
                "emoji": "🚁",
                "title": "helicopter"
            },
            {
                "emoji": "🚟",
                "title": "suspension railway"
            },
            {
                "emoji": "🚠",
                "title": "mountain cableway"
            },
            {
                "emoji": "🚡",
                "title": "aerial tramway"
            },
            {
                "emoji": "🛰️",
                "title": "satellite"
            },
            {
                "emoji": "🚀",
                "title": "rocket"
            },
            {
                "emoji": "🛸",
                "title": "flying saucer"
            },
            {
                "emoji": "🛎️",
                "title": "bellhop bell"
            },
            {
                "emoji": "🧳",
                "title": "luggage"
            },
            {
                "emoji": "⌛",
                "title": "hourglass done"
            },
            {
                "emoji": "⏳",
                "title": "hourglass not done"
            },
            {
                "emoji": "⌚",
                "title": "watch"
            },
            {
                "emoji": "⏰",
                "title": "alarm clock"
            },
            {
                "emoji": "⏱️",
                "title": "stopwatch"
            },
            {
                "emoji": "⏲️",
                "title": "timer clock"
            },
            {
                "emoji": "🕰️",
                "title": "mantelpiece clock"
            },
            {
                "emoji": "🕛",
                "title": "twelve o’clock"
            },
            {
                "emoji": "🕧",
                "title": "twelve-thirty"
            },
            {
                "emoji": "🕐",
                "title": "one o’clock"
            },
            {
                "emoji": "🕜",
                "title": "one-thirty"
            },
            {
                "emoji": "🕑",
                "title": "two o’clock"
            },
            {
                "emoji": "🕝",
                "title": "two-thirty"
            },
            {
                "emoji": "🕒",
                "title": "three o’clock"
            },
            {
                "emoji": "🕞",
                "title": "three-thirty"
            },
            {
                "emoji": "🕓",
                "title": "four o’clock"
            },
            {
                "emoji": "🕟",
                "title": "four-thirty"
            },
            {
                "emoji": "🕔",
                "title": "five o’clock"
            },
            {
                "emoji": "🕠",
                "title": "five-thirty"
            },
            {
                "emoji": "🕕",
                "title": "six o’clock"
            },
            {
                "emoji": "🕡",
                "title": "six-thirty"
            },
            {
                "emoji": "🕖",
                "title": "seven o’clock"
            },
            {
                "emoji": "🕢",
                "title": "seven-thirty"
            },
            {
                "emoji": "🕗",
                "title": "eight o’clock"
            },
            {
                "emoji": "🕣",
                "title": "eight-thirty"
            },
            {
                "emoji": "🕘",
                "title": "nine o’clock"
            },
            {
                "emoji": "🕤",
                "title": "nine-thirty"
            },
            {
                "emoji": "🕙",
                "title": "ten o’clock"
            },
            {
                "emoji": "🕥",
                "title": "ten-thirty"
            },
            {
                "emoji": "🕚",
                "title": "eleven o’clock"
            },
            {
                "emoji": "🕦",
                "title": "eleven-thirty"
            },
            {
                "emoji": "🌑",
                "title": "new moon"
            },
            {
                "emoji": "🌒",
                "title": "waxing crescent moon"
            },
            {
                "emoji": "🌓",
                "title": "first quarter moon"
            },
            {
                "emoji": "🌔",
                "title": "waxing gibbous moon"
            },
            {
                "emoji": "🌕",
                "title": "full moon"
            },
            {
                "emoji": "🌖",
                "title": "waning gibbous moon"
            },
            {
                "emoji": "🌗",
                "title": "last quarter moon"
            },
            {
                "emoji": "🌘",
                "title": "waning crescent moon"
            },
            {
                "emoji": "🌙",
                "title": "crescent moon"
            },
            {
                "emoji": "🌚",
                "title": "new moon face"
            },
            {
                "emoji": "🌛",
                "title": "first quarter moon face"
            },
            {
                "emoji": "🌜",
                "title": "last quarter moon face"
            },
            {
                "emoji": "🌡️",
                "title": "thermometer"
            },
            {
                "emoji": "☀️",
                "title": "sun"
            },
            {
                "emoji": "🌝",
                "title": "full moon face"
            },
            {
                "emoji": "🌞",
                "title": "sun with face"
            },
            {
                "emoji": "🪐",
                "title": "ringed planet"
            },
            {
                "emoji": "⭐",
                "title": "star"
            },
            {
                "emoji": "🌟",
                "title": "glowing star"
            },
            {
                "emoji": "🌠",
                "title": "shooting star"
            },
            {
                "emoji": "🌌",
                "title": "milky way"
            },
            {
                "emoji": "☁️",
                "title": "cloud"
            },
            {
                "emoji": "⛅",
                "title": "sun behind cloud"
            },
            {
                "emoji": "⛈️",
                "title": "cloud with lightning and rain"
            },
            {
                "emoji": "🌤️",
                "title": "sun behind small cloud"
            },
            {
                "emoji": "🌥️",
                "title": "sun behind large cloud"
            },
            {
                "emoji": "🌦️",
                "title": "sun behind rain cloud"
            },
            {
                "emoji": "🌧️",
                "title": "cloud with rain"
            },
            {
                "emoji": "🌨️",
                "title": "cloud with snow"
            },
            {
                "emoji": "🌩️",
                "title": "cloud with lightning"
            },
            {
                "emoji": "🌪️",
                "title": "tornado"
            },
            {
                "emoji": "🌫️",
                "title": "fog"
            },
            {
                "emoji": "🌬️",
                "title": "wind face"
            },
            {
                "emoji": "🌀",
                "title": "cyclone"
            },
            {
                "emoji": "🌈",
                "title": "rainbow"
            },
            {
                "emoji": "🌂",
                "title": "closed umbrella"
            },
            {
                "emoji": "☂️",
                "title": "umbrella"
            },
            {
                "emoji": "☔",
                "title": "umbrella with rain drops"
            },
            {
                "emoji": "⛱️",
                "title": "umbrella on ground"
            },
            {
                "emoji": "⚡",
                "title": "high voltage"
            },
            {
                "emoji": "❄️",
                "title": "snowflake"
            },
            {
                "emoji": "☃️",
                "title": "snowman"
            },
            {
                "emoji": "⛄",
                "title": "snowman without snow"
            },
            {
                "emoji": "☄️",
                "title": "comet"
            },
            {
                "emoji": "🪋",
                "v": 18,
                "title": "meteor"
            },
            {
                "emoji": "🔥",
                "title": "fire"
            },
            {
                "emoji": "💧",
                "title": "droplet"
            },
            {
                "emoji": "🌊",
                "title": "water wave"
            }
        ],
        'Objects': [
            {
                "emoji": "👓",
                "title": "glasses"
            },
            {
                "emoji": "🕶️",
                "title": "sunglasses"
            },
            {
                "emoji": "🥽",
                "title": "goggles"
            },
            {
                "emoji": "🥼",
                "title": "lab coat"
            },
            {
                "emoji": "🦺",
                "title": "safety vest"
            },
            {
                "emoji": "👔",
                "title": "necktie"
            },
            {
                "emoji": "👕",
                "title": "t-shirt"
            },
            {
                "emoji": "👖",
                "title": "jeans"
            },
            {
                "emoji": "🧣",
                "title": "scarf"
            },
            {
                "emoji": "🧤",
                "title": "gloves"
            },
            {
                "emoji": "🧥",
                "title": "coat"
            },
            {
                "emoji": "🧦",
                "title": "socks"
            },
            {
                "emoji": "👗",
                "title": "dress"
            },
            {
                "emoji": "👘",
                "title": "kimono"
            },
            {
                "emoji": "🥻",
                "title": "sari"
            },
            {
                "emoji": "🩱",
                "title": "one-piece swimsuit"
            },
            {
                "emoji": "🩲",
                "title": "briefs"
            },
            {
                "emoji": "🩳",
                "title": "shorts"
            },
            {
                "emoji": "👙",
                "title": "bikini"
            },
            {
                "emoji": "👚",
                "title": "woman’s clothes"
            },
            {
                "emoji": "🪭",
                "title": "folding hand fan"
            },
            {
                "emoji": "👛",
                "title": "purse"
            },
            {
                "emoji": "👜",
                "title": "handbag"
            },
            {
                "emoji": "👝",
                "title": "clutch bag"
            },
            {
                "emoji": "🛍️",
                "title": "shopping bags"
            },
            {
                "emoji": "🎒",
                "title": "backpack"
            },
            {
                "emoji": "🩴",
                "title": "thong sandal"
            },
            {
                "emoji": "👞",
                "title": "man’s shoe"
            },
            {
                "emoji": "👟",
                "title": "running shoe"
            },
            {
                "emoji": "🥾",
                "title": "hiking boot"
            },
            {
                "emoji": "🥿",
                "title": "flat shoe"
            },
            {
                "emoji": "👠",
                "title": "high-heeled shoe"
            },
            {
                "emoji": "👡",
                "title": "woman’s sandal"
            },
            {
                "emoji": "🩰",
                "title": "ballet shoes"
            },
            {
                "emoji": "👢",
                "title": "woman’s boot"
            },
            {
                "emoji": "🪮",
                "title": "hair pick"
            },
            {
                "emoji": "👑",
                "title": "crown"
            },
            {
                "emoji": "👒",
                "title": "woman’s hat"
            },
            {
                "emoji": "🎩",
                "title": "top hat"
            },
            {
                "emoji": "🎓",
                "title": "graduation cap"
            },
            {
                "emoji": "🧢",
                "title": "billed cap"
            },
            {
                "emoji": "🪖",
                "title": "military helmet"
            },
            {
                "emoji": "⛑️",
                "title": "rescue worker’s helmet"
            },
            {
                "emoji": "📿",
                "title": "prayer beads"
            },
            {
                "emoji": "💄",
                "title": "lipstick"
            },
            {
                "emoji": "💍",
                "title": "ring"
            },
            {
                "emoji": "💎",
                "title": "gem stone"
            },
            {
                "emoji": "🔇",
                "title": "muted speaker"
            },
            {
                "emoji": "🔈",
                "title": "speaker low volume"
            },
            {
                "emoji": "🔉",
                "title": "speaker medium volume"
            },
            {
                "emoji": "🔊",
                "title": "speaker high volume"
            },
            {
                "emoji": "📢",
                "title": "loudspeaker"
            },
            {
                "emoji": "📣",
                "title": "megaphone"
            },
            {
                "emoji": "📯",
                "title": "postal horn"
            },
            {
                "emoji": "🔔",
                "title": "bell"
            },
            {
                "emoji": "🔕",
                "title": "bell with slash"
            },
            {
                "emoji": "🎼",
                "title": "musical score"
            },
            {
                "emoji": "🎵",
                "title": "musical note"
            },
            {
                "emoji": "🎶",
                "title": "musical notes"
            },
            {
                "emoji": "🎙️",
                "title": "studio microphone"
            },
            {
                "emoji": "🎚️",
                "title": "level slider"
            },
            {
                "emoji": "🎛️",
                "title": "control knobs"
            },
            {
                "emoji": "🎤",
                "title": "microphone"
            },
            {
                "emoji": "🎧",
                "title": "headphone"
            },
            {
                "emoji": "📻",
                "title": "radio"
            },
            {
                "emoji": "🎷",
                "title": "saxophone"
            },
            {
                "emoji": "🎺",
                "title": "trumpet"
            },
            {
                "emoji": "🪊",
                "v": 17,
                "title": "trombone"
            },
            {
                "emoji": "🪗",
                "title": "accordion"
            },
            {
                "emoji": "🎸",
                "title": "guitar"
            },
            {
                "emoji": "🎹",
                "title": "musical keyboard"
            },
            {
                "emoji": "🎻",
                "title": "violin"
            },
            {
                "emoji": "🪕",
                "title": "banjo"
            },
            {
                "emoji": "🥁",
                "title": "drum"
            },
            {
                "emoji": "🪘",
                "title": "long drum"
            },
            {
                "emoji": "🪇",
                "title": "maracas"
            },
            {
                "emoji": "🪈",
                "title": "flute"
            },
            {
                "emoji": "🪉",
                "title": "harp"
            },
            {
                "emoji": "📱",
                "title": "mobile phone"
            },
            {
                "emoji": "📲",
                "title": "mobile phone with arrow"
            },
            {
                "emoji": "☎️",
                "title": "telephone"
            },
            {
                "emoji": "📞",
                "title": "telephone receiver"
            },
            {
                "emoji": "📟",
                "title": "pager"
            },
            {
                "emoji": "📠",
                "title": "fax machine"
            },
            {
                "emoji": "🔋",
                "title": "battery"
            },
            {
                "emoji": "🪫",
                "title": "low battery"
            },
            {
                "emoji": "🔌",
                "title": "electric plug"
            },
            {
                "emoji": "💻",
                "title": "laptop"
            },
            {
                "emoji": "🖥️",
                "title": "desktop computer"
            },
            {
                "emoji": "🖨️",
                "title": "printer"
            },
            {
                "emoji": "⌨️",
                "title": "keyboard"
            },
            {
                "emoji": "🖱️",
                "title": "computer mouse"
            },
            {
                "emoji": "🖲️",
                "title": "trackball"
            },
            {
                "emoji": "💽",
                "title": "computer disk"
            },
            {
                "emoji": "💾",
                "title": "floppy disk"
            },
            {
                "emoji": "💿",
                "title": "optical disk"
            },
            {
                "emoji": "📀",
                "title": "dvd"
            },
            {
                "emoji": "🧮",
                "title": "abacus"
            },
            {
                "emoji": "🎥",
                "title": "movie camera"
            },
            {
                "emoji": "🎞️",
                "title": "film frames"
            },
            {
                "emoji": "📽️",
                "title": "film projector"
            },
            {
                "emoji": "🎬",
                "title": "clapper board"
            },
            {
                "emoji": "📺",
                "title": "television"
            },
            {
                "emoji": "📷",
                "title": "camera"
            },
            {
                "emoji": "📸",
                "title": "camera with flash"
            },
            {
                "emoji": "📹",
                "title": "video camera"
            },
            {
                "emoji": "📼",
                "title": "videocassette"
            },
            {
                "emoji": "🔍",
                "title": "magnifying glass tilted left"
            },
            {
                "emoji": "🔎",
                "title": "magnifying glass tilted right"
            },
            {
                "emoji": "🕯️",
                "title": "candle"
            },
            {
                "emoji": "💡",
                "title": "light bulb"
            },
            {
                "emoji": "🔦",
                "title": "flashlight"
            },
            {
                "emoji": "🏮",
                "title": "red paper lantern"
            },
            {
                "emoji": "🪔",
                "title": "diya lamp"
            },
            {
                "emoji": "📔",
                "title": "notebook with decorative cover"
            },
            {
                "emoji": "📕",
                "title": "closed book"
            },
            {
                "emoji": "📖",
                "title": "open book"
            },
            {
                "emoji": "📗",
                "title": "green book"
            },
            {
                "emoji": "📘",
                "title": "blue book"
            },
            {
                "emoji": "📙",
                "title": "orange book"
            },
            {
                "emoji": "📚",
                "title": "books"
            },
            {
                "emoji": "📓",
                "title": "notebook"
            },
            {
                "emoji": "📒",
                "title": "ledger"
            },
            {
                "emoji": "📃",
                "title": "page with curl"
            },
            {
                "emoji": "📜",
                "title": "scroll"
            },
            {
                "emoji": "📄",
                "title": "page facing up"
            },
            {
                "emoji": "📰",
                "title": "newspaper"
            },
            {
                "emoji": "🗞️",
                "title": "rolled-up newspaper"
            },
            {
                "emoji": "📑",
                "title": "bookmark tabs"
            },
            {
                "emoji": "🔖",
                "title": "bookmark"
            },
            {
                "emoji": "🏷️",
                "title": "label"
            },
            {
                "emoji": "🪙",
                "title": "coin"
            },
            {
                "emoji": "💰",
                "title": "money bag"
            },
            {
                "emoji": "🪎",
                "v": 17,
                "title": "treasure chest"
            },
            {
                "emoji": "💴",
                "title": "yen banknote"
            },
            {
                "emoji": "💵",
                "title": "dollar banknote"
            },
            {
                "emoji": "💶",
                "title": "euro banknote"
            },
            {
                "emoji": "💷",
                "title": "pound banknote"
            },
            {
                "emoji": "💸",
                "title": "money with wings"
            },
            {
                "emoji": "💳",
                "title": "credit card"
            },
            {
                "emoji": "🧾",
                "title": "receipt"
            },
            {
                "emoji": "💹",
                "title": "chart increasing with yen"
            },
            {
                "emoji": "✉️",
                "title": "envelope"
            },
            {
                "emoji": "📧",
                "title": "e-mail"
            },
            {
                "emoji": "📨",
                "title": "incoming envelope"
            },
            {
                "emoji": "📩",
                "title": "envelope with arrow"
            },
            {
                "emoji": "📤",
                "title": "outbox tray"
            },
            {
                "emoji": "📥",
                "title": "inbox tray"
            },
            {
                "emoji": "📦",
                "title": "package"
            },
            {
                "emoji": "📫",
                "title": "closed mailbox with raised flag"
            },
            {
                "emoji": "📪",
                "title": "closed mailbox with lowered flag"
            },
            {
                "emoji": "📬",
                "title": "open mailbox with raised flag"
            },
            {
                "emoji": "📭",
                "title": "open mailbox with lowered flag"
            },
            {
                "emoji": "📮",
                "title": "postbox"
            },
            {
                "emoji": "🗳️",
                "title": "ballot box with ballot"
            },
            {
                "emoji": "✏️",
                "title": "pencil"
            },
            {
                "emoji": "✒️",
                "title": "black nib"
            },
            {
                "emoji": "🖋️",
                "title": "fountain pen"
            },
            {
                "emoji": "🖊️",
                "title": "pen"
            },
            {
                "emoji": "🖌️",
                "title": "paintbrush"
            },
            {
                "emoji": "🖍️",
                "title": "crayon"
            },
            {
                "emoji": "📝",
                "title": "memo"
            },
            {
                "emoji": "🪌",
                "v": 18,
                "title": "eraser"
            },
            {
                "emoji": "💼",
                "title": "briefcase"
            },
            {
                "emoji": "📁",
                "title": "file folder"
            },
            {
                "emoji": "📂",
                "title": "open file folder"
            },
            {
                "emoji": "🗂️",
                "title": "card index dividers"
            },
            {
                "emoji": "📅",
                "title": "calendar"
            },
            {
                "emoji": "📆",
                "title": "tear-off calendar"
            },
            {
                "emoji": "🗒️",
                "title": "spiral notepad"
            },
            {
                "emoji": "🗓️",
                "title": "spiral calendar"
            },
            {
                "emoji": "📇",
                "title": "card index"
            },
            {
                "emoji": "📈",
                "title": "chart increasing"
            },
            {
                "emoji": "📉",
                "title": "chart decreasing"
            },
            {
                "emoji": "📊",
                "title": "bar chart"
            },
            {
                "emoji": "📋",
                "title": "clipboard"
            },
            {
                "emoji": "📌",
                "title": "pushpin"
            },
            {
                "emoji": "📍",
                "title": "round pushpin"
            },
            {
                "emoji": "📎",
                "title": "paperclip"
            },
            {
                "emoji": "🖇️",
                "title": "linked paperclips"
            },
            {
                "emoji": "📏",
                "title": "straight ruler"
            },
            {
                "emoji": "📐",
                "title": "triangular ruler"
            },
            {
                "emoji": "✂️",
                "title": "scissors"
            },
            {
                "emoji": "🗃️",
                "title": "card file box"
            },
            {
                "emoji": "🗄️",
                "title": "file cabinet"
            },
            {
                "emoji": "🗑️",
                "title": "wastebasket"
            },
            {
                "emoji": "🔒",
                "title": "locked"
            },
            {
                "emoji": "🔓",
                "title": "unlocked"
            },
            {
                "emoji": "🔏",
                "title": "locked with pen"
            },
            {
                "emoji": "🔐",
                "title": "locked with key"
            },
            {
                "emoji": "🔑",
                "title": "key"
            },
            {
                "emoji": "🗝️",
                "title": "old key"
            },
            {
                "emoji": "🪍",
                "v": 18,
                "title": "net with handle"
            },
            {
                "emoji": "🔨",
                "title": "hammer"
            },
            {
                "emoji": "🪓",
                "title": "axe"
            },
            {
                "emoji": "⛏️",
                "title": "pick"
            },
            {
                "emoji": "⚒️",
                "title": "hammer and pick"
            },
            {
                "emoji": "🛠️",
                "title": "hammer and wrench"
            },
            {
                "emoji": "🗡️",
                "title": "dagger"
            },
            {
                "emoji": "⚔️",
                "title": "crossed swords"
            },
            {
                "emoji": "💣",
                "title": "bomb"
            },
            {
                "emoji": "🪃",
                "title": "boomerang"
            },
            {
                "emoji": "🏹",
                "title": "bow and arrow"
            },
            {
                "emoji": "🛡️",
                "title": "shield"
            },
            {
                "emoji": "🪚",
                "title": "carpentry saw"
            },
            {
                "emoji": "🔧",
                "title": "wrench"
            },
            {
                "emoji": "🪛",
                "title": "screwdriver"
            },
            {
                "emoji": "🔩",
                "title": "nut and bolt"
            },
            {
                "emoji": "⚙️",
                "title": "gear"
            },
            {
                "emoji": "🗜️",
                "title": "clamp"
            },
            {
                "emoji": "⚖️",
                "title": "balance scale"
            },
            {
                "emoji": "🦯",
                "title": "white cane"
            },
            {
                "emoji": "🔗",
                "title": "link"
            },
            {
                "emoji": "⛓️‍💥",
                "title": "broken chain"
            },
            {
                "emoji": "⛓️",
                "title": "chains"
            },
            {
                "emoji": "🪝",
                "title": "hook"
            },
            {
                "emoji": "🧰",
                "title": "toolbox"
            },
            {
                "emoji": "🧲",
                "title": "magnet"
            },
            {
                "emoji": "🪜",
                "title": "ladder"
            },
            {
                "emoji": "🪏",
                "title": "shovel"
            },
            {
                "emoji": "⚗️",
                "title": "alembic"
            },
            {
                "emoji": "🧪",
                "title": "test tube"
            },
            {
                "emoji": "🧫",
                "title": "petri dish"
            },
            {
                "emoji": "🧬",
                "title": "dna"
            },
            {
                "emoji": "🔬",
                "title": "microscope"
            },
            {
                "emoji": "🔭",
                "title": "telescope"
            },
            {
                "emoji": "📡",
                "title": "satellite antenna"
            },
            {
                "emoji": "💉",
                "title": "syringe"
            },
            {
                "emoji": "🩸",
                "title": "drop of blood"
            },
            {
                "emoji": "💊",
                "title": "pill"
            },
            {
                "emoji": "🩹",
                "title": "adhesive bandage"
            },
            {
                "emoji": "🩼",
                "title": "crutch"
            },
            {
                "emoji": "🩺",
                "title": "stethoscope"
            },
            {
                "emoji": "🩻",
                "title": "x-ray"
            },
            {
                "emoji": "🚪",
                "title": "door"
            },
            {
                "emoji": "🛗",
                "title": "elevator"
            },
            {
                "emoji": "🪞",
                "title": "mirror"
            },
            {
                "emoji": "🪟",
                "title": "window"
            },
            {
                "emoji": "🛏️",
                "title": "bed"
            },
            {
                "emoji": "🛋️",
                "title": "couch and lamp"
            },
            {
                "emoji": "🪑",
                "title": "chair"
            },
            {
                "emoji": "🚽",
                "title": "toilet"
            },
            {
                "emoji": "🪠",
                "title": "plunger"
            },
            {
                "emoji": "🚿",
                "title": "shower"
            },
            {
                "emoji": "🛁",
                "title": "bathtub"
            },
            {
                "emoji": "🪤",
                "title": "mouse trap"
            },
            {
                "emoji": "🪒",
                "title": "razor"
            },
            {
                "emoji": "🧴",
                "title": "lotion bottle"
            },
            {
                "emoji": "🧷",
                "title": "safety pin"
            },
            {
                "emoji": "🧹",
                "title": "broom"
            },
            {
                "emoji": "🧺",
                "title": "basket"
            },
            {
                "emoji": "🧻",
                "title": "roll of paper"
            },
            {
                "emoji": "🪣",
                "title": "bucket"
            },
            {
                "emoji": "🧼",
                "title": "soap"
            },
            {
                "emoji": "🫧",
                "title": "bubbles"
            },
            {
                "emoji": "🪥",
                "title": "toothbrush"
            },
            {
                "emoji": "🧽",
                "title": "sponge"
            },
            {
                "emoji": "🧯",
                "title": "fire extinguisher"
            },
            {
                "emoji": "🛒",
                "title": "shopping cart"
            },
            {
                "emoji": "🚬",
                "title": "cigarette"
            },
            {
                "emoji": "⚰️",
                "title": "coffin"
            },
            {
                "emoji": "🪦",
                "title": "headstone"
            },
            {
                "emoji": "⚱️",
                "title": "funeral urn"
            },
            {
                "emoji": "🧿",
                "title": "nazar amulet"
            },
            {
                "emoji": "🪬",
                "title": "hamsa"
            },
            {
                "emoji": "🗿",
                "title": "moai"
            },
            {
                "emoji": "🪧",
                "title": "placard"
            },
            {
                "emoji": "🪪",
                "title": "identification card"
            }
        ],
        'Symbols': [
            {
                "emoji": "🏧",
                "title": "ATM sign"
            },
            {
                "emoji": "🚮",
                "title": "litter in bin sign"
            },
            {
                "emoji": "🚰",
                "title": "potable water"
            },
            {
                "emoji": "♿",
                "title": "wheelchair symbol"
            },
            {
                "emoji": "🚹",
                "title": "men’s room"
            },
            {
                "emoji": "🚺",
                "title": "women’s room"
            },
            {
                "emoji": "🚻",
                "title": "restroom"
            },
            {
                "emoji": "🚼",
                "title": "baby symbol"
            },
            {
                "emoji": "🚾",
                "title": "water closet"
            },
            {
                "emoji": "🛂",
                "title": "passport control"
            },
            {
                "emoji": "🛃",
                "title": "customs"
            },
            {
                "emoji": "🛄",
                "title": "baggage claim"
            },
            {
                "emoji": "🛅",
                "title": "left luggage"
            },
            {
                "emoji": "⚠️",
                "title": "warning"
            },
            {
                "emoji": "🚸",
                "title": "children crossing"
            },
            {
                "emoji": "⛔",
                "title": "no entry"
            },
            {
                "emoji": "🚫",
                "title": "prohibited"
            },
            {
                "emoji": "🚳",
                "title": "no bicycles"
            },
            {
                "emoji": "🚭",
                "title": "no smoking"
            },
            {
                "emoji": "🚯",
                "title": "no littering"
            },
            {
                "emoji": "🚱",
                "title": "non-potable water"
            },
            {
                "emoji": "🚷",
                "title": "no pedestrians"
            },
            {
                "emoji": "📵",
                "title": "no mobile phones"
            },
            {
                "emoji": "🔞",
                "title": "no one under eighteen"
            },
            {
                "emoji": "☢️",
                "title": "radioactive"
            },
            {
                "emoji": "☣️",
                "title": "biohazard"
            },
            {
                "emoji": "⬆️",
                "title": "up arrow"
            },
            {
                "emoji": "↗️",
                "title": "up-right arrow"
            },
            {
                "emoji": "➡️",
                "title": "right arrow"
            },
            {
                "emoji": "↘️",
                "title": "down-right arrow"
            },
            {
                "emoji": "⬇️",
                "title": "down arrow"
            },
            {
                "emoji": "↙️",
                "title": "down-left arrow"
            },
            {
                "emoji": "⬅️",
                "title": "left arrow"
            },
            {
                "emoji": "↖️",
                "title": "up-left arrow"
            },
            {
                "emoji": "↕️",
                "title": "up-down arrow"
            },
            {
                "emoji": "↔️",
                "title": "left-right arrow"
            },
            {
                "emoji": "↩️",
                "title": "right arrow curving left"
            },
            {
                "emoji": "↪️",
                "title": "left arrow curving right"
            },
            {
                "emoji": "⤴️",
                "title": "right arrow curving up"
            },
            {
                "emoji": "⤵️",
                "title": "right arrow curving down"
            },
            {
                "emoji": "🔃",
                "title": "clockwise vertical arrows"
            },
            {
                "emoji": "🔄",
                "title": "counterclockwise arrows button"
            },
            {
                "emoji": "🔙",
                "title": "BACK arrow"
            },
            {
                "emoji": "🔚",
                "title": "END arrow"
            },
            {
                "emoji": "🔛",
                "title": "ON! arrow"
            },
            {
                "emoji": "🔜",
                "title": "SOON arrow"
            },
            {
                "emoji": "🔝",
                "title": "TOP arrow"
            },
            {
                "emoji": "🛐",
                "title": "place of worship"
            },
            {
                "emoji": "⚛️",
                "title": "atom symbol"
            },
            {
                "emoji": "🕉️",
                "title": "om"
            },
            {
                "emoji": "✡️",
                "title": "star of David"
            },
            {
                "emoji": "☸️",
                "title": "wheel of dharma"
            },
            {
                "emoji": "☯️",
                "title": "yin yang"
            },
            {
                "emoji": "✝️",
                "title": "latin cross"
            },
            {
                "emoji": "☦️",
                "title": "orthodox cross"
            },
            {
                "emoji": "☪️",
                "title": "star and crescent"
            },
            {
                "emoji": "☮️",
                "title": "peace symbol"
            },
            {
                "emoji": "🕎",
                "title": "menorah"
            },
            {
                "emoji": "🔯",
                "title": "dotted six-pointed star"
            },
            {
                "emoji": "🪯",
                "title": "khanda"
            },
            {
                "emoji": "♈",
                "title": "Aries"
            },
            {
                "emoji": "♉",
                "title": "Taurus"
            },
            {
                "emoji": "♊",
                "title": "Gemini"
            },
            {
                "emoji": "♋",
                "title": "Cancer"
            },
            {
                "emoji": "♌",
                "title": "Leo"
            },
            {
                "emoji": "♍",
                "title": "Virgo"
            },
            {
                "emoji": "♎",
                "title": "Libra"
            },
            {
                "emoji": "♏",
                "title": "Scorpio"
            },
            {
                "emoji": "♐",
                "title": "Sagittarius"
            },
            {
                "emoji": "♑",
                "title": "Capricorn"
            },
            {
                "emoji": "♒",
                "title": "Aquarius"
            },
            {
                "emoji": "♓",
                "title": "Pisces"
            },
            {
                "emoji": "⛎",
                "title": "Ophiuchus"
            },
            {
                "emoji": "🔀",
                "title": "shuffle tracks button"
            },
            {
                "emoji": "🔁",
                "title": "repeat button"
            },
            {
                "emoji": "🔂",
                "title": "repeat single button"
            },
            {
                "emoji": "▶️",
                "title": "play button"
            },
            {
                "emoji": "⏩",
                "title": "fast-forward button"
            },
            {
                "emoji": "⏭️",
                "title": "next track button"
            },
            {
                "emoji": "⏯️",
                "title": "play or pause button"
            },
            {
                "emoji": "◀️",
                "title": "reverse button"
            },
            {
                "emoji": "⏪",
                "title": "fast reverse button"
            },
            {
                "emoji": "⏮️",
                "title": "last track button"
            },
            {
                "emoji": "🔼",
                "title": "upwards button"
            },
            {
                "emoji": "⏫",
                "title": "fast up button"
            },
            {
                "emoji": "🔽",
                "title": "downwards button"
            },
            {
                "emoji": "⏬",
                "title": "fast down button"
            },
            {
                "emoji": "⏸️",
                "title": "pause button"
            },
            {
                "emoji": "⏹️",
                "title": "stop button"
            },
            {
                "emoji": "⏺️",
                "title": "record button"
            },
            {
                "emoji": "⏏️",
                "title": "eject button"
            },
            {
                "emoji": "🎦",
                "title": "cinema"
            },
            {
                "emoji": "🔅",
                "title": "dim button"
            },
            {
                "emoji": "🔆",
                "title": "bright button"
            },
            {
                "emoji": "📶",
                "title": "antenna bars"
            },
            {
                "emoji": "🛜",
                "title": "wireless"
            },
            {
                "emoji": "📳",
                "title": "vibration mode"
            },
            {
                "emoji": "📴",
                "title": "mobile phone off"
            },
            {
                "emoji": "♀️",
                "title": "female sign"
            },
            {
                "emoji": "♂️",
                "title": "male sign"
            },
            {
                "emoji": "⚧️",
                "title": "transgender symbol"
            },
            {
                "emoji": "✖️",
                "title": "multiply"
            },
            {
                "emoji": "➕",
                "title": "plus"
            },
            {
                "emoji": "➖",
                "title": "minus"
            },
            {
                "emoji": "➗",
                "title": "divide"
            },
            {
                "emoji": "🟰",
                "title": "heavy equals sign"
            },
            {
                "emoji": "♾️",
                "title": "infinity"
            },
            {
                "emoji": "‼️",
                "title": "double exclamation mark"
            },
            {
                "emoji": "⁉️",
                "title": "exclamation question mark"
            },
            {
                "emoji": "❓",
                "title": "red question mark"
            },
            {
                "emoji": "❔",
                "title": "white question mark"
            },
            {
                "emoji": "❕",
                "title": "white exclamation mark"
            },
            {
                "emoji": "❗",
                "title": "red exclamation mark"
            },
            {
                "emoji": "〰️",
                "title": "wavy dash"
            },
            {
                "emoji": "💱",
                "title": "currency exchange"
            },
            {
                "emoji": "💲",
                "title": "heavy dollar sign"
            },
            {
                "emoji": "⚕️",
                "title": "medical symbol"
            },
            {
                "emoji": "♻️",
                "title": "recycling symbol"
            },
            {
                "emoji": "⚜️",
                "title": "fleur-de-lis"
            },
            {
                "emoji": "🔱",
                "title": "trident emblem"
            },
            {
                "emoji": "📛",
                "title": "name badge"
            },
            {
                "emoji": "🔰",
                "title": "Japanese symbol for beginner"
            },
            {
                "emoji": "⭕",
                "title": "hollow red circle"
            },
            {
                "emoji": "✅",
                "title": "check mark button"
            },
            {
                "emoji": "☑️",
                "title": "check box with check"
            },
            {
                "emoji": "✔️",
                "title": "check mark"
            },
            {
                "emoji": "❌",
                "title": "cross mark"
            },
            {
                "emoji": "❎",
                "title": "cross mark button"
            },
            {
                "emoji": "➰",
                "title": "curly loop"
            },
            {
                "emoji": "➿",
                "title": "double curly loop"
            },
            {
                "emoji": "〽️",
                "title": "part alternation mark"
            },
            {
                "emoji": "✳️",
                "title": "eight-spoked asterisk"
            },
            {
                "emoji": "✴️",
                "title": "eight-pointed star"
            },
            {
                "emoji": "❇️",
                "title": "sparkle"
            },
            {
                "emoji": "©️",
                "title": "copyright"
            },
            {
                "emoji": "®️",
                "title": "registered"
            },
            {
                "emoji": "™️",
                "title": "trade mark"
            },
            {
                "emoji": "🫟",
                "title": "splatter"
            },
            {
                "emoji": "#️⃣",
                "title": "keycap: #"
            },
            {
                "emoji": "*️⃣",
                "title": "keycap: *"
            },
            {
                "emoji": "0️⃣",
                "title": "keycap: 0"
            },
            {
                "emoji": "1️⃣",
                "title": "keycap: 1"
            },
            {
                "emoji": "2️⃣",
                "title": "keycap: 2"
            },
            {
                "emoji": "3️⃣",
                "title": "keycap: 3"
            },
            {
                "emoji": "4️⃣",
                "title": "keycap: 4"
            },
            {
                "emoji": "5️⃣",
                "title": "keycap: 5"
            },
            {
                "emoji": "6️⃣",
                "title": "keycap: 6"
            },
            {
                "emoji": "7️⃣",
                "title": "keycap: 7"
            },
            {
                "emoji": "8️⃣",
                "title": "keycap: 8"
            },
            {
                "emoji": "9️⃣",
                "title": "keycap: 9"
            },
            {
                "emoji": "🔟",
                "title": "keycap: 10"
            },
            {
                "emoji": "🔠",
                "title": "input latin uppercase"
            },
            {
                "emoji": "🔡",
                "title": "input latin lowercase"
            },
            {
                "emoji": "🔢",
                "title": "input numbers"
            },
            {
                "emoji": "🔣",
                "title": "input symbols"
            },
            {
                "emoji": "🔤",
                "title": "input latin letters"
            },
            {
                "emoji": "🅰️",
                "title": "A button (blood type)"
            },
            {
                "emoji": "🆎",
                "title": "AB button (blood type)"
            },
            {
                "emoji": "🅱️",
                "title": "B button (blood type)"
            },
            {
                "emoji": "🆑",
                "title": "CL button"
            },
            {
                "emoji": "🆒",
                "title": "COOL button"
            },
            {
                "emoji": "🆓",
                "title": "FREE button"
            },
            {
                "emoji": "ℹ️",
                "title": "information"
            },
            {
                "emoji": "🆔",
                "title": "ID button"
            },
            {
                "emoji": "Ⓜ️",
                "title": "circled M"
            },
            {
                "emoji": "🆕",
                "title": "NEW button"
            },
            {
                "emoji": "🆖",
                "title": "NG button"
            },
            {
                "emoji": "🅾️",
                "title": "O button (blood type)"
            },
            {
                "emoji": "🆗",
                "title": "OK button"
            },
            {
                "emoji": "🅿️",
                "title": "P button"
            },
            {
                "emoji": "🆘",
                "title": "SOS button"
            },
            {
                "emoji": "🆙",
                "title": "UP! button"
            },
            {
                "emoji": "🆚",
                "title": "VS button"
            },
            {
                "emoji": "🈁",
                "title": "Japanese “here” button"
            },
            {
                "emoji": "🈂️",
                "title": "Japanese “service charge” button"
            },
            {
                "emoji": "🈷️",
                "title": "Japanese “monthly amount” button"
            },
            {
                "emoji": "🈶",
                "title": "Japanese “not free of charge” button"
            },
            {
                "emoji": "🈯",
                "title": "Japanese “reserved” button"
            },
            {
                "emoji": "🉐",
                "title": "Japanese “bargain” button"
            },
            {
                "emoji": "🈹",
                "title": "Japanese “discount” button"
            },
            {
                "emoji": "🈚",
                "title": "Japanese “free of charge” button"
            },
            {
                "emoji": "🈲",
                "title": "Japanese “prohibited” button"
            },
            {
                "emoji": "🉑",
                "title": "Japanese “acceptable” button"
            },
            {
                "emoji": "🈸",
                "title": "Japanese “application” button"
            },
            {
                "emoji": "🈴",
                "title": "Japanese “passing grade” button"
            },
            {
                "emoji": "🈳",
                "title": "Japanese “vacancy” button"
            },
            {
                "emoji": "㊗️",
                "title": "Japanese “congratulations” button"
            },
            {
                "emoji": "㊙️",
                "title": "Japanese “secret” button"
            },
            {
                "emoji": "🈺",
                "title": "Japanese “open for business” button"
            },
            {
                "emoji": "🈵",
                "title": "Japanese “no vacancy” button"
            },
            {
                "emoji": "🔴",
                "title": "red circle"
            },
            {
                "emoji": "🟠",
                "title": "orange circle"
            },
            {
                "emoji": "🟡",
                "title": "yellow circle"
            },
            {
                "emoji": "🟢",
                "title": "green circle"
            },
            {
                "emoji": "🔵",
                "title": "blue circle"
            },
            {
                "emoji": "🟣",
                "title": "purple circle"
            },
            {
                "emoji": "🟤",
                "title": "brown circle"
            },
            {
                "emoji": "⚫",
                "title": "black circle"
            },
            {
                "emoji": "⚪",
                "title": "white circle"
            },
            {
                "emoji": "🟥",
                "title": "red square"
            },
            {
                "emoji": "🟧",
                "title": "orange square"
            },
            {
                "emoji": "🟨",
                "title": "yellow square"
            },
            {
                "emoji": "🟩",
                "title": "green square"
            },
            {
                "emoji": "🟦",
                "title": "blue square"
            },
            {
                "emoji": "🟪",
                "title": "purple square"
            },
            {
                "emoji": "🟫",
                "title": "brown square"
            },
            {
                "emoji": "⬛",
                "title": "black large square"
            },
            {
                "emoji": "⬜",
                "title": "white large square"
            },
            {
                "emoji": "◼️",
                "title": "black medium square"
            },
            {
                "emoji": "◻️",
                "title": "white medium square"
            },
            {
                "emoji": "◾",
                "title": "black medium-small square"
            },
            {
                "emoji": "◽",
                "title": "white medium-small square"
            },
            {
                "emoji": "▪️",
                "title": "black small square"
            },
            {
                "emoji": "▫️",
                "title": "white small square"
            },
            {
                "emoji": "🔶",
                "title": "large orange diamond"
            },
            {
                "emoji": "🔷",
                "title": "large blue diamond"
            },
            {
                "emoji": "🔸",
                "title": "small orange diamond"
            },
            {
                "emoji": "🔹",
                "title": "small blue diamond"
            },
            {
                "emoji": "🔺",
                "title": "red triangle pointed up"
            },
            {
                "emoji": "🔻",
                "title": "red triangle pointed down"
            },
            {
                "emoji": "💠",
                "title": "diamond with a dot"
            },
            {
                "emoji": "🔘",
                "title": "radio button"
            },
            {
                "emoji": "🔳",
                "title": "white square button"
            },
            {
                "emoji": "🔲",
                "title": "black square button"
            }
        ],
        'Flags': [
            {
                "emoji": "🏁",
                "title": "chequered flag"
            },
            {
                "emoji": "🚩",
                "title": "triangular flag"
            },
            {
                "emoji": "🎌",
                "title": "crossed flags"
            },
            {
                "emoji": "🏴",
                "title": "black flag"
            },
            {
                "emoji": "🏳️",
                "title": "white flag"
            },
            {
                "emoji": "🏳️‍🌈",
                "title": "rainbow flag"
            },
            {
                "emoji": "🏳️‍⚧️",
                "title": "transgender flag"
            },
            {
                "emoji": "🏴‍☠️",
                "title": "pirate flag"
            },
            {
                "emoji": "🇦🇨",
                "title": "flag: Ascension Island"
            },
            {
                "emoji": "🇦🇩",
                "title": "flag: Andorra"
            },
            {
                "emoji": "🇦🇪",
                "title": "flag: United Arab Emirates"
            },
            {
                "emoji": "🇦🇫",
                "title": "flag: Afghanistan"
            },
            {
                "emoji": "🇦🇬",
                "title": "flag: Antigua & Barbuda"
            },
            {
                "emoji": "🇦🇮",
                "title": "flag: Anguilla"
            },
            {
                "emoji": "🇦🇱",
                "title": "flag: Albania"
            },
            {
                "emoji": "🇦🇲",
                "title": "flag: Armenia"
            },
            {
                "emoji": "🇦🇴",
                "title": "flag: Angola"
            },
            {
                "emoji": "🇦🇶",
                "title": "flag: Antarctica"
            },
            {
                "emoji": "🇦🇷",
                "title": "flag: Argentina"
            },
            {
                "emoji": "🇦🇸",
                "title": "flag: American Samoa"
            },
            {
                "emoji": "🇦🇹",
                "title": "flag: Austria"
            },
            {
                "emoji": "🇦🇺",
                "title": "flag: Australia"
            },
            {
                "emoji": "🇦🇼",
                "title": "flag: Aruba"
            },
            {
                "emoji": "🇦🇽",
                "title": "flag: Åland Islands"
            },
            {
                "emoji": "🇦🇿",
                "title": "flag: Azerbaijan"
            },
            {
                "emoji": "🇧🇦",
                "title": "flag: Bosnia & Herzegovina"
            },
            {
                "emoji": "🇧🇧",
                "title": "flag: Barbados"
            },
            {
                "emoji": "🇧🇩",
                "title": "flag: Bangladesh"
            },
            {
                "emoji": "🇧🇪",
                "title": "flag: Belgium"
            },
            {
                "emoji": "🇧🇫",
                "title": "flag: Burkina Faso"
            },
            {
                "emoji": "🇧🇬",
                "title": "flag: Bulgaria"
            },
            {
                "emoji": "🇧🇭",
                "title": "flag: Bahrain"
            },
            {
                "emoji": "🇧🇮",
                "title": "flag: Burundi"
            },
            {
                "emoji": "🇧🇯",
                "title": "flag: Benin"
            },
            {
                "emoji": "🇧🇱",
                "title": "flag: St. Barthélemy"
            },
            {
                "emoji": "🇧🇲",
                "title": "flag: Bermuda"
            },
            {
                "emoji": "🇧🇳",
                "title": "flag: Brunei"
            },
            {
                "emoji": "🇧🇴",
                "title": "flag: Bolivia"
            },
            {
                "emoji": "🇧🇶",
                "title": "flag: Caribbean Netherlands"
            },
            {
                "emoji": "🇧🇷",
                "title": "flag: Brazil"
            },
            {
                "emoji": "🇧🇸",
                "title": "flag: Bahamas"
            },
            {
                "emoji": "🇧🇹",
                "title": "flag: Bhutan"
            },
            {
                "emoji": "🇧🇻",
                "title": "flag: Bouvet Island"
            },
            {
                "emoji": "🇧🇼",
                "title": "flag: Botswana"
            },
            {
                "emoji": "🇧🇾",
                "title": "flag: Belarus"
            },
            {
                "emoji": "🇧🇿",
                "title": "flag: Belize"
            },
            {
                "emoji": "🇨🇦",
                "title": "flag: Canada"
            },
            {
                "emoji": "🇨🇨",
                "title": "flag: Cocos (Keeling) Islands"
            },
            {
                "emoji": "🇨🇩",
                "title": "flag: Congo - Kinshasa"
            },
            {
                "emoji": "🇨🇫",
                "title": "flag: Central African Republic"
            },
            {
                "emoji": "🇨🇬",
                "title": "flag: Congo - Brazzaville"
            },
            {
                "emoji": "🇨🇭",
                "title": "flag: Switzerland"
            },
            {
                "emoji": "🇨🇮",
                "title": "flag: Côte d’Ivoire"
            },
            {
                "emoji": "🇨🇰",
                "title": "flag: Cook Islands"
            },
            {
                "emoji": "🇨🇱",
                "title": "flag: Chile"
            },
            {
                "emoji": "🇨🇲",
                "title": "flag: Cameroon"
            },
            {
                "emoji": "🇨🇳",
                "title": "flag: China"
            },
            {
                "emoji": "🇨🇴",
                "title": "flag: Colombia"
            },
            {
                "emoji": "🇨🇵",
                "title": "flag: Clipperton Island"
            },
            {
                "emoji": "🇨🇶",
                "title": "flag: Sark"
            },
            {
                "emoji": "🇨🇷",
                "title": "flag: Costa Rica"
            },
            {
                "emoji": "🇨🇺",
                "title": "flag: Cuba"
            },
            {
                "emoji": "🇨🇻",
                "title": "flag: Cape Verde"
            },
            {
                "emoji": "🇨🇼",
                "title": "flag: Curaçao"
            },
            {
                "emoji": "🇨🇽",
                "title": "flag: Christmas Island"
            },
            {
                "emoji": "🇨🇾",
                "title": "flag: Cyprus"
            },
            {
                "emoji": "🇨🇿",
                "title": "flag: Czechia"
            },
            {
                "emoji": "🇩🇪",
                "title": "flag: Germany"
            },
            {
                "emoji": "🇩🇬",
                "title": "flag: Diego Garcia"
            },
            {
                "emoji": "🇩🇯",
                "title": "flag: Djibouti"
            },
            {
                "emoji": "🇩🇰",
                "title": "flag: Denmark"
            },
            {
                "emoji": "🇩🇲",
                "title": "flag: Dominica"
            },
            {
                "emoji": "🇩🇴",
                "title": "flag: Dominican Republic"
            },
            {
                "emoji": "🇩🇿",
                "title": "flag: Algeria"
            },
            {
                "emoji": "🇪🇦",
                "title": "flag: Ceuta & Melilla"
            },
            {
                "emoji": "🇪🇨",
                "title": "flag: Ecuador"
            },
            {
                "emoji": "🇪🇪",
                "title": "flag: Estonia"
            },
            {
                "emoji": "🇪🇬",
                "title": "flag: Egypt"
            },
            {
                "emoji": "🇪🇭",
                "title": "flag: Western Sahara"
            },
            {
                "emoji": "🇪🇷",
                "title": "flag: Eritrea"
            },
            {
                "emoji": "🇪🇸",
                "title": "flag: Spain"
            },
            {
                "emoji": "🇪🇹",
                "title": "flag: Ethiopia"
            },
            {
                "emoji": "🇪🇺",
                "title": "flag: European Union"
            },
            {
                "emoji": "🇫🇮",
                "title": "flag: Finland"
            },
            {
                "emoji": "🇫🇯",
                "title": "flag: Fiji"
            },
            {
                "emoji": "🇫🇰",
                "title": "flag: Falkland Islands"
            },
            {
                "emoji": "🇫🇲",
                "title": "flag: Micronesia"
            },
            {
                "emoji": "🇫🇴",
                "title": "flag: Faroe Islands"
            },
            {
                "emoji": "🇫🇷",
                "title": "flag: France"
            },
            {
                "emoji": "🇬🇦",
                "title": "flag: Gabon"
            },
            {
                "emoji": "🇬🇧",
                "title": "flag: United Kingdom"
            },
            {
                "emoji": "🇬🇩",
                "title": "flag: Grenada"
            },
            {
                "emoji": "🇬🇪",
                "title": "flag: Georgia"
            },
            {
                "emoji": "🇬🇫",
                "title": "flag: French Guiana"
            },
            {
                "emoji": "🇬🇬",
                "title": "flag: Guernsey"
            },
            {
                "emoji": "🇬🇭",
                "title": "flag: Ghana"
            },
            {
                "emoji": "🇬🇮",
                "title": "flag: Gibraltar"
            },
            {
                "emoji": "🇬🇱",
                "title": "flag: Greenland"
            },
            {
                "emoji": "🇬🇲",
                "title": "flag: Gambia"
            },
            {
                "emoji": "🇬🇳",
                "title": "flag: Guinea"
            },
            {
                "emoji": "🇬🇵",
                "title": "flag: Guadeloupe"
            },
            {
                "emoji": "🇬🇶",
                "title": "flag: Equatorial Guinea"
            },
            {
                "emoji": "🇬🇷",
                "title": "flag: Greece"
            },
            {
                "emoji": "🇬🇸",
                "title": "flag: South Georgia & South Sandwich Islands"
            },
            {
                "emoji": "🇬🇹",
                "title": "flag: Guatemala"
            },
            {
                "emoji": "🇬🇺",
                "title": "flag: Guam"
            },
            {
                "emoji": "🇬🇼",
                "title": "flag: Guinea-Bissau"
            },
            {
                "emoji": "🇬🇾",
                "title": "flag: Guyana"
            },
            {
                "emoji": "🇭🇰",
                "title": "flag: Hong Kong SAR China"
            },
            {
                "emoji": "🇭🇲",
                "title": "flag: Heard Island & McDonald Islands"
            },
            {
                "emoji": "🇭🇳",
                "title": "flag: Honduras"
            },
            {
                "emoji": "🇭🇷",
                "title": "flag: Croatia"
            },
            {
                "emoji": "🇭🇹",
                "title": "flag: Haiti"
            },
            {
                "emoji": "🇭🇺",
                "title": "flag: Hungary"
            },
            {
                "emoji": "🇮🇨",
                "title": "flag: Canary Islands"
            },
            {
                "emoji": "🇮🇩",
                "title": "flag: Indonesia"
            },
            {
                "emoji": "🇮🇪",
                "title": "flag: Ireland"
            },
            {
                "emoji": "🇮🇱",
                "title": "flag: Israel"
            },
            {
                "emoji": "🇮🇲",
                "title": "flag: Isle of Man"
            },
            {
                "emoji": "🇮🇳",
                "title": "flag: India"
            },
            {
                "emoji": "🇮🇴",
                "title": "flag: British Indian Ocean Territory"
            },
            {
                "emoji": "🇮🇶",
                "title": "flag: Iraq"
            },
            {
                "emoji": "🇮🇷",
                "title": "flag: Iran"
            },
            {
                "emoji": "🇮🇸",
                "title": "flag: Iceland"
            },
            {
                "emoji": "🇮🇹",
                "title": "flag: Italy"
            },
            {
                "emoji": "🇯🇪",
                "title": "flag: Jersey"
            },
            {
                "emoji": "🇯🇲",
                "title": "flag: Jamaica"
            },
            {
                "emoji": "🇯🇴",
                "title": "flag: Jordan"
            },
            {
                "emoji": "🇯🇵",
                "title": "flag: Japan"
            },
            {
                "emoji": "🇰🇪",
                "title": "flag: Kenya"
            },
            {
                "emoji": "🇰🇬",
                "title": "flag: Kyrgyzstan"
            },
            {
                "emoji": "🇰🇭",
                "title": "flag: Cambodia"
            },
            {
                "emoji": "🇰🇮",
                "title": "flag: Kiribati"
            },
            {
                "emoji": "🇰🇲",
                "title": "flag: Comoros"
            },
            {
                "emoji": "🇰🇳",
                "title": "flag: St. Kitts & Nevis"
            },
            {
                "emoji": "🇰🇵",
                "title": "flag: North Korea"
            },
            {
                "emoji": "🇰🇷",
                "title": "flag: South Korea"
            },
            {
                "emoji": "🇰🇼",
                "title": "flag: Kuwait"
            },
            {
                "emoji": "🇰🇾",
                "title": "flag: Cayman Islands"
            },
            {
                "emoji": "🇰🇿",
                "title": "flag: Kazakhstan"
            },
            {
                "emoji": "🇱🇦",
                "title": "flag: Laos"
            },
            {
                "emoji": "🇱🇧",
                "title": "flag: Lebanon"
            },
            {
                "emoji": "🇱🇨",
                "title": "flag: St. Lucia"
            },
            {
                "emoji": "🇱🇮",
                "title": "flag: Liechtenstein"
            },
            {
                "emoji": "🇱🇰",
                "title": "flag: Sri Lanka"
            },
            {
                "emoji": "🇱🇷",
                "title": "flag: Liberia"
            },
            {
                "emoji": "🇱🇸",
                "title": "flag: Lesotho"
            },
            {
                "emoji": "🇱🇹",
                "title": "flag: Lithuania"
            },
            {
                "emoji": "🇱🇺",
                "title": "flag: Luxembourg"
            },
            {
                "emoji": "🇱🇻",
                "title": "flag: Latvia"
            },
            {
                "emoji": "🇱🇾",
                "title": "flag: Libya"
            },
            {
                "emoji": "🇲🇦",
                "title": "flag: Morocco"
            },
            {
                "emoji": "🇲🇨",
                "title": "flag: Monaco"
            },
            {
                "emoji": "🇲🇩",
                "title": "flag: Moldova"
            },
            {
                "emoji": "🇲🇪",
                "title": "flag: Montenegro"
            },
            {
                "emoji": "🇲🇫",
                "title": "flag: St. Martin"
            },
            {
                "emoji": "🇲🇬",
                "title": "flag: Madagascar"
            },
            {
                "emoji": "🇲🇭",
                "title": "flag: Marshall Islands"
            },
            {
                "emoji": "🇲🇰",
                "title": "flag: North Macedonia"
            },
            {
                "emoji": "🇲🇱",
                "title": "flag: Mali"
            },
            {
                "emoji": "🇲🇲",
                "title": "flag: Myanmar (Burma)"
            },
            {
                "emoji": "🇲🇳",
                "title": "flag: Mongolia"
            },
            {
                "emoji": "🇲🇴",
                "title": "flag: Macao SAR China"
            },
            {
                "emoji": "🇲🇵",
                "title": "flag: Northern Mariana Islands"
            },
            {
                "emoji": "🇲🇶",
                "title": "flag: Martinique"
            },
            {
                "emoji": "🇲🇷",
                "title": "flag: Mauritania"
            },
            {
                "emoji": "🇲🇸",
                "title": "flag: Montserrat"
            },
            {
                "emoji": "🇲🇹",
                "title": "flag: Malta"
            },
            {
                "emoji": "🇲🇺",
                "title": "flag: Mauritius"
            },
            {
                "emoji": "🇲🇻",
                "title": "flag: Maldives"
            },
            {
                "emoji": "🇲🇼",
                "title": "flag: Malawi"
            },
            {
                "emoji": "🇲🇽",
                "title": "flag: Mexico"
            },
            {
                "emoji": "🇲🇾",
                "title": "flag: Malaysia"
            },
            {
                "emoji": "🇲🇿",
                "title": "flag: Mozambique"
            },
            {
                "emoji": "🇳🇦",
                "title": "flag: Namibia"
            },
            {
                "emoji": "🇳🇨",
                "title": "flag: New Caledonia"
            },
            {
                "emoji": "🇳🇪",
                "title": "flag: Niger"
            },
            {
                "emoji": "🇳🇫",
                "title": "flag: Norfolk Island"
            },
            {
                "emoji": "🇳🇬",
                "title": "flag: Nigeria"
            },
            {
                "emoji": "🇳🇮",
                "title": "flag: Nicaragua"
            },
            {
                "emoji": "🇳🇱",
                "title": "flag: Netherlands"
            },
            {
                "emoji": "🇳🇴",
                "title": "flag: Norway"
            },
            {
                "emoji": "🇳🇵",
                "title": "flag: Nepal"
            },
            {
                "emoji": "🇳🇷",
                "title": "flag: Nauru"
            },
            {
                "emoji": "🇳🇺",
                "title": "flag: Niue"
            },
            {
                "emoji": "🇳🇿",
                "title": "flag: New Zealand"
            },
            {
                "emoji": "🇴🇲",
                "title": "flag: Oman"
            },
            {
                "emoji": "🇵🇦",
                "title": "flag: Panama"
            },
            {
                "emoji": "🇵🇪",
                "title": "flag: Peru"
            },
            {
                "emoji": "🇵🇫",
                "title": "flag: French Polynesia"
            },
            {
                "emoji": "🇵🇬",
                "title": "flag: Papua New Guinea"
            },
            {
                "emoji": "🇵🇭",
                "title": "flag: Philippines"
            },
            {
                "emoji": "🇵🇰",
                "title": "flag: Pakistan"
            },
            {
                "emoji": "🇵🇱",
                "title": "flag: Poland"
            },
            {
                "emoji": "🇵🇲",
                "title": "flag: St. Pierre & Miquelon"
            },
            {
                "emoji": "🇵🇳",
                "title": "flag: Pitcairn Islands"
            },
            {
                "emoji": "🇵🇷",
                "title": "flag: Puerto Rico"
            },
            {
                "emoji": "🇵🇸",
                "title": "flag: Palestinian Territories"
            },
            {
                "emoji": "🇵🇹",
                "title": "flag: Portugal"
            },
            {
                "emoji": "🇵🇼",
                "title": "flag: Palau"
            },
            {
                "emoji": "🇵🇾",
                "title": "flag: Paraguay"
            },
            {
                "emoji": "🇶🇦",
                "title": "flag: Qatar"
            },
            {
                "emoji": "🇷🇪",
                "title": "flag: Réunion"
            },
            {
                "emoji": "🇷🇴",
                "title": "flag: Romania"
            },
            {
                "emoji": "🇷🇸",
                "title": "flag: Serbia"
            },
            {
                "emoji": "🇷🇺",
                "title": "flag: Russia"
            },
            {
                "emoji": "🇷🇼",
                "title": "flag: Rwanda"
            },
            {
                "emoji": "🇸🇦",
                "title": "flag: Saudi Arabia"
            },
            {
                "emoji": "🇸🇧",
                "title": "flag: Solomon Islands"
            },
            {
                "emoji": "🇸🇨",
                "title": "flag: Seychelles"
            },
            {
                "emoji": "🇸🇩",
                "title": "flag: Sudan"
            },
            {
                "emoji": "🇸🇪",
                "title": "flag: Sweden"
            },
            {
                "emoji": "🇸🇬",
                "title": "flag: Singapore"
            },
            {
                "emoji": "🇸🇭",
                "title": "flag: St. Helena, Ascension & Tristan da Cunha"
            },
            {
                "emoji": "🇸🇮",
                "title": "flag: Slovenia"
            },
            {
                "emoji": "🇸🇯",
                "title": "flag: Svalbard & Jan Mayen"
            },
            {
                "emoji": "🇸🇰",
                "title": "flag: Slovakia"
            },
            {
                "emoji": "🇸🇱",
                "title": "flag: Sierra Leone"
            },
            {
                "emoji": "🇸🇲",
                "title": "flag: San Marino"
            },
            {
                "emoji": "🇸🇳",
                "title": "flag: Senegal"
            },
            {
                "emoji": "🇸🇴",
                "title": "flag: Somalia"
            },
            {
                "emoji": "🇸🇷",
                "title": "flag: Suriname"
            },
            {
                "emoji": "🇸🇸",
                "title": "flag: South Sudan"
            },
            {
                "emoji": "🇸🇹",
                "title": "flag: São Tomé & Príncipe"
            },
            {
                "emoji": "🇸🇻",
                "title": "flag: El Salvador"
            },
            {
                "emoji": "🇸🇽",
                "title": "flag: Sint Maarten"
            },
            {
                "emoji": "🇸🇾",
                "title": "flag: Syria"
            },
            {
                "emoji": "🇸🇿",
                "title": "flag: Eswatini"
            },
            {
                "emoji": "🇹🇦",
                "title": "flag: Tristan da Cunha"
            },
            {
                "emoji": "🇹🇨",
                "title": "flag: Turks & Caicos Islands"
            },
            {
                "emoji": "🇹🇩",
                "title": "flag: Chad"
            },
            {
                "emoji": "🇹🇫",
                "title": "flag: French Southern and Antarctic Lands"
            },
            {
                "emoji": "🇹🇬",
                "title": "flag: Togo"
            },
            {
                "emoji": "🇹🇭",
                "title": "flag: Thailand"
            },
            {
                "emoji": "🇹🇯",
                "title": "flag: Tajikistan"
            },
            {
                "emoji": "🇹🇰",
                "title": "flag: Tokelau"
            },
            {
                "emoji": "🇹🇱",
                "title": "flag: Timor-Leste"
            },
            {
                "emoji": "🇹🇲",
                "title": "flag: Turkmenistan"
            },
            {
                "emoji": "🇹🇳",
                "title": "flag: Tunisia"
            },
            {
                "emoji": "🇹🇴",
                "title": "flag: Tonga"
            },
            {
                "emoji": "🇹🇷",
                "title": "flag: Türkiye"
            },
            {
                "emoji": "🇹🇹",
                "title": "flag: Trinidad & Tobago"
            },
            {
                "emoji": "🇹🇻",
                "title": "flag: Tuvalu"
            },
            {
                "emoji": "🇹🇼",
                "title": "flag: Taiwan"
            },
            {
                "emoji": "🇹🇿",
                "title": "flag: Tanzania"
            },
            {
                "emoji": "🇺🇦",
                "title": "flag: Ukraine"
            },
            {
                "emoji": "🇺🇬",
                "title": "flag: Uganda"
            },
            {
                "emoji": "🇺🇲",
                "title": "flag: U.S. Outlying Islands"
            },
            {
                "emoji": "🇺🇳",
                "title": "flag: United Nations"
            },
            {
                "emoji": "🇺🇸",
                "title": "flag: United States"
            },
            {
                "emoji": "🇺🇾",
                "title": "flag: Uruguay"
            },
            {
                "emoji": "🇺🇿",
                "title": "flag: Uzbekistan"
            },
            {
                "emoji": "🇻🇦",
                "title": "flag: Vatican City"
            },
            {
                "emoji": "🇻🇨",
                "title": "flag: St. Vincent & Grenadines"
            },
            {
                "emoji": "🇻🇪",
                "title": "flag: Venezuela"
            },
            {
                "emoji": "🇻🇬",
                "title": "flag: British Virgin Islands"
            },
            {
                "emoji": "🇻🇮",
                "title": "flag: U.S. Virgin Islands"
            },
            {
                "emoji": "🇻🇳",
                "title": "flag: Vietnam"
            },
            {
                "emoji": "🇻🇺",
                "title": "flag: Vanuatu"
            },
            {
                "emoji": "🇼🇫",
                "title": "flag: Wallis & Futuna"
            },
            {
                "emoji": "🇼🇸",
                "title": "flag: Samoa"
            },
            {
                "emoji": "🇽🇰",
                "title": "flag: Kosovo"
            },
            {
                "emoji": "🇾🇪",
                "title": "flag: Yemen"
            },
            {
                "emoji": "🇾🇹",
                "title": "flag: Mayotte"
            },
            {
                "emoji": "🇿🇦",
                "title": "flag: South Africa"
            },
            {
                "emoji": "🇿🇲",
                "title": "flag: Zambia"
            },
            {
                "emoji": "🇿🇼",
                "title": "flag: Zimbabwe"
            },
            {
                "emoji": "🏴󠁧󠁢󠁥󠁮󠁧󠁿",
                "title": "flag: England"
            },
            {
                "emoji": "🏴󠁧󠁢󠁳󠁣󠁴󠁿",
                "title": "flag: Scotland"
            },
            {
                "emoji": "🏴󠁧󠁢󠁷󠁬󠁳󠁿",
                "title": "flag: Wales"
            }
        ]
    };

    const categoryFlags = {
        'Frequent': '<svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="M256 0C114.8 0 0 114.8 0 256s114.8 256 256 256 256-114.8 256-256S397.2 0 256 0zm0 472c-119.1 0-216-96.9-216-216S136.9 40 256 40s216 96.9 216 216-96.9 216-216 216z"/><path d="M276 130h-40v140l102 61 20-34-82-49z"/></svg>',
        'Smileys': '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve"> <g> <g> <path d="M437.02,74.98C388.667,26.629,324.38,0,256,0S123.333,26.629,74.98,74.98C26.629,123.333,0,187.62,0,256 s26.629,132.668,74.98,181.02C123.333,485.371,187.62,512,256,512s132.667-26.629,181.02-74.98 C485.371,388.668,512,324.38,512,256S485.371,123.333,437.02,74.98z M256,472c-119.103,0-216-96.897-216-216S136.897,40,256,40 s216,96.897,216,216S375.103,472,256,472z"/> </g> </g> <g> <g> <path d="M368.993,285.776c-0.072,0.214-7.298,21.626-25.02,42.393C321.419,354.599,292.628,368,258.4,368 c-34.475,0-64.195-13.561-88.333-40.303c-18.92-20.962-27.272-42.54-27.33-42.691l-37.475,13.99 c0.42,1.122,10.533,27.792,34.013,54.273C171.022,389.074,212.215,408,258.4,408c46.412,0,86.904-19.076,117.099-55.166 c22.318-26.675,31.165-53.55,31.531-54.681L368.993,285.776z"/> </g> </g> <g> <g> <circle cx="168" cy="180.12" r="32"/> </g> </g> <g> <g> <circle cx="344" cy="180.12" r="32"/> </g> </g> <g> </g> <g> </g> <g> </g> </svg>',
        'People': '<svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><circle cx="256" cy="88" r="56"/><path d="M176 176h160c22 0 40 18 40 40v136h-44v144h-60V376h-32v136h-60V352h-44V216c0-22 18-40 40-40z"/></svg>',
        'Nature': '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 354.968 354.968" style="enable-background:new 0 0 354.968 354.968;" xml:space="preserve"> <g> <g> <path d="M350.775,341.319c-9.6-28.4-20.8-55.2-34.4-80.8c0.4-0.4,0.8-1.2,1.6-1.6c30.8-34.8,44-83.6,20.4-131.6 c-20.4-41.6-65.6-76.4-124.8-98.8c-57.2-22-127.6-32.4-200.4-27.2c-5.6,0.4-10,5.2-9.6,10.8c0.4,2.8,1.6,5.6,4,7.2 c36.8,31.6,50,79.2,63.6,126.8c8,28,15.6,55.6,28.4,81.2c0,0.4,0.4,0.4,0.4,0.8c30.8,59.6,78,81.2,122.8,78.4 c18.4-1.2,36-6.4,52.4-14.4c9.2-4.8,18-10.4,26-16.8c11.6,23.2,22,47.2,30.4,72.8c1.6,5.2,7.6,8,12.8,6.4 C349.975,352.119,352.775,346.519,350.775,341.319z M271.175,189.319c-34.8-44.4-78-82.4-131.6-112.4c-4.8-2.8-11.2-1.2-13.6,4 c-2.8,4.8-1.2,11.2,4,13.6c50.8,28.8,92.4,64.8,125.6,107.2c13.2,17.2,25.2,35.2,36,54c-8,7.6-16.4,13.6-25.6,18 c-14,7.2-28.8,11.6-44.4,12.4c-37.6,2.4-77.2-16-104-67.6v-0.4c-11.6-24-19.2-50.8-26.8-78c-12.4-43.2-24.4-86.4-53.6-120.4 c61.6-1.6,120.4,8.4,169.2,27.2c54.4,20.8,96,52,114,88.8c18.8,38,9.2,76.8-14.4,105.2 C295.575,222.919,283.975,205.719,271.175,189.319z"/> </g> </g> <g> </g> <g> </g> <g> </g> </svg>',
        'Food-dring': '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 295 295" xmlns:xlink="http://www.w3.org/1999/xlink" enable-background="new 0 0 295 295"> <g> <path d="M25,226.011v16.511c0,8.836,7.465,16.489,16.302,16.489h214.063c8.837,0,15.636-7.653,15.636-16.489v-16.511H25z"/> <path d="m271.83,153.011c-3.635-66-57.634-117.022-123.496-117.022-65.863,0-119.863,51.021-123.498,117.022h246.994zm-198.497-50.99c-4.557,0-8.25-3.693-8.25-8.25 0-4.557 3.693-8.25 8.25-8.25 4.557,0 8.25,3.693 8.25,8.25 0,4.557-3.693,8.25-8.25,8.25zm42,33c-4.557,0-8.25-3.693-8.25-8.25 0-4.557 3.693-8.25 8.25-8.25 4.557,0 8.25,3.693 8.25,8.25 0,4.557-3.693,8.25-8.25,8.25zm33.248-58c-4.557,0-8.25-3.693-8.25-8.25 0-4.557 3.693-8.25 8.25-8.25 4.557,0 8.25,3.693 8.25,8.25 0,4.557-3.693,8.25-8.25,8.25zm32.752,58c-4.557,0-8.25-3.693-8.25-8.25 0-4.557 3.693-8.25 8.25-8.25 4.557,0 8.25,3.693 8.25,8.25 0,4.557-3.693,8.25-8.25,8.25zm50.25-41.25c0,4.557-3.693,8.25-8.25,8.25-4.557,0-8.25-3.693-8.25-8.25 0-4.557 3.693-8.25 8.25-8.25 4.557,0 8.25,3.694 8.25,8.25z"/> <path d="m275.414,169.011h-0.081-254.825c-11.142,0-20.508,8.778-20.508,19.921v0.414c0,11.143 9.366,20.665 20.508,20.665h254.906c11.142,0 19.586-9.523 19.586-20.665v-0.414c0-11.143-8.444-19.921-19.586-19.921z"/> </g> </svg>',
        'Activity': '<svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path id="XMLID_272_" d="m437.02 74.98c-48.353-48.351-112.64-74.98-181.02-74.98s-132.667 26.629-181.02 74.98c-48.351 48.353-74.98 112.64-74.98 181.02s26.629 132.667 74.98 181.02c48.353 48.351 112.64 74.98 181.02 74.98s132.667-26.629 181.02-74.98c48.351-48.353 74.98-112.64 74.98-181.02s-26.629-132.667-74.98-181.02zm-407.02 181.02c0-57.102 21.297-109.316 56.352-149.142 37.143 45.142 57.438 101.499 57.438 160.409 0 53.21-16.914 105.191-47.908 148.069-40.693-40.891-65.882-97.226-65.882-159.336zm88.491 179.221c35.75-48.412 55.3-107.471 55.3-167.954 0-66.866-23.372-130.794-66.092-181.661 39.718-34.614 91.603-55.606 148.301-55.606 56.585 0 108.376 20.906 148.064 55.396-42.834 50.9-66.269 114.902-66.269 181.872 0 60.556 19.605 119.711 55.448 168.158-38.077 29.193-85.665 46.574-137.243 46.574-51.698 0-99.388-17.461-137.509-46.779zm297.392-19.645c-31.104-42.922-48.088-95.008-48.088-148.309 0-59.026 20.367-115.47 57.638-160.651 35.182 39.857 56.567 92.166 56.567 149.384 0 62.23-25.284 118.665-66.117 159.576z"/></svg>',
        'Travel-places': '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 1000 1000" enable-background="new 0 0 1000 1000" xml:space="preserve"> <g><g><path d="M846.5,153.5C939,246.1,990,369.1,990,500c0,130.9-51,253.9-143.5,346.5C753.9,939,630.9,990,500,990c-130.9,0-253.9-51-346.5-143.5C61,753.9,10,630.9,10,500c0-130.9,51-253.9,143.5-346.5C246.1,61,369.1,10,500,10C630.9,10,753.9,61,846.5,153.5z M803.2,803.2c60.3-60.3,100.5-135.5,117-217.3c-12.9,19-25.2,26-32.9-16.5c-7.9-69.3-71.5-25-111.5-49.6c-42.1,28.4-136.8-55.2-120.7,39.1c24.8,42.5,134-56.9,79.6,33.1c-34.7,62.8-126.9,201.9-114.9,274c1.5,105-107.3,21.9-144.8-12.9c-25.2-69.8-8.6-191.8-74.6-225.9c-71.6-3.1-133-9.6-160.8-89.6c-16.7-57.3,17.8-142.5,79.1-155.7c89.8-56.4,121.9,66.1,206.1,68.4c26.2-27.4,97.4-36.1,103.4-66.8c-55.3-9.8,70.1-46.5-5.3-67.4c-41.6,4.9-68.4,43.1-46.3,75.6C496,410.3,493.5,274.8,416,317.6c-2,67.6-126.5,21.9-43.1,8.2c28.7-12.5-46.8-48.8-6-42.2c20-1.1,87.4-24.7,69.2-40.6c37.5-23.3,69.1,55.8,105.8-1.8c26.5-44.3-11.1-52.5-44.4-30c-18.7-21,33.1-66.3,78.8-85.9c15.2-6.5,29.8-10.1,40.9-9.1c23,26.6,65.6,31.2,67.8-3.2c-57-27.3-119.9-41.7-185-41.7c-93.4,0-182.3,29.7-255.8,84.6c19.8,9.1,31,20.3,11.9,34.7c-14.8,44.1-74.8,103.2-127.5,94.9c-27.4,47.2-45.4,99.2-53.1,153.6c44.1,14.6,54.3,43.5,44.8,53.2c-22.5,19.6-36.3,47.4-43.4,77.8C91.3,658,132.6,739,196.8,803.2c81,81,188.6,125.6,303.2,125.6C614.5,928.8,722.2,884.2,803.2,803.2z"/></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></g> </svg>',
        'Objects': '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 461.977 461.977" style="enable-background:new 0 0 461.977 461.977;" xml:space="preserve"> <g> <path d="M398.47,248.268L346.376,18.543C344.136,8.665,333.287,0,323.158,0H138.821c-10.129,0-20.979,8.665-23.219,18.543 L63.507,248.268c-0.902,3.979-0.271,7.582,1.775,10.145c2.047,2.564,5.421,3.975,9.501,3.975h51.822v39.108 c-6.551,3.555-11,10.493-11,18.47c0,11.598,9.402,21,21,21c11.598,0,21-9.402,21-21c0-7.978-4.449-14.916-11-18.47v-39.108h240.587 c4.079,0,7.454-1.412,9.501-3.975C398.742,255.849,399.372,252.247,398.47,248.268z"/> <path d="M318.735,441.977h-77.747V282.388h-20v159.588h-77.747c-5.523,0-10,4.477-10,10c0,5.523,4.477,10,10,10h175.494 c5.522,0,10-4.477,10-10C328.735,446.454,324.257,441.977,318.735,441.977z"/> </g> <g> </g> <g> </g> <g> </g> </svg>',
        'Symbols': '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 30.487 30.486" style="enable-background:new 0 0 30.487 30.486;" xml:space="preserve"> <g> <path d="M28.866,17.477h-2.521V15.03h-2.56c0.005-2.8-0.304-5.204-0.315-5.308l-0.088-0.67L22.75,8.811 c-0.021-0.008-0.142-0.051-0.317-0.109l2.287-8.519L19,4.836L15.23,0.022V0l-0.009,0.01L15.215,0v0.021l-3.769,4.815L5.725,0.183 l2.299,8.561c-0.157,0.051-0.268,0.09-0.288,0.098L7.104,9.084l-0.088,0.67c-0.013,0.104-0.321,2.508-0.316,5.308h-2.56v2.446H1.62 l0.447,2.514L1.62,22.689h6.474c1.907,2.966,5.186,7.549,7.162,7.797v-0.037c1.979-0.283,5.237-4.838,7.137-7.79h6.474l-0.447-2.67 L28.866,17.477z M21.137,20.355c-0.422,1.375-4.346,6.949-5.907,7.758v0.015c-1.577-0.853-5.461-6.373-5.882-7.739 c-0.002-0.043-0.005-0.095-0.008-0.146l11.804-0.031C21.141,20.264,21.139,20.314,21.137,20.355z M8.972,15.062 c-0.003-1.769,0.129-3.403,0.219-4.298c0.98-0.271,3.072-0.723,6.065-0.78v-0.03c2.979,0.06,5.063,0.51,6.04,0.779 c0.09,0.895,0.223,2.529,0.219,4.298L8.972,15.062z"/> </g> <g> </g> <g> </g> <g> </g> </svg>',
        'Flags': '<svg viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"><g id="Page-1" fill-rule="evenodd"><g id="037---Waypoint-Flag" fill-rule="nonzero" transform="translate(0 -1)"><path id="Shape" d="m59.0752 28.5054c-3.7664123-1.873859-7.2507049-4.2678838-10.3506-7.1118 1.5923634-6.0211307 2.7737841-12.14349669 3.5361-18.3248.1788-1.44-.623-1.9047-.872-2.0126-.7016942-.26712004-1.4944908-.00419148-1.8975.6293-5.4726 6.5479-12.9687 5.8008-20.9053 5.0054-7.9985-.8-16.2506-1.6116-22.3684 5.4114-.85552122-1.067885-2.26533581-1.5228479-3.5837-1.1565l-.1377.0386c-1.81412367.5095218-2.87378593 2.391025-2.3691 4.2065l12.2089 43.6891c.3541969 1.2645215 1.5052141 2.1399137 2.8184 2.1435.2677318-.0003961.5341685-.0371657.792-.1093l1.0683-.2984h.001c.7485787-.2091577 1.3833789-.7071796 1.7646969-1.3844635.381318-.677284.4779045-1.478326.2685031-2.2268365l-3.7812-13.5327c5.5066-7.0807 13.18-6.3309 21.2988-5.52 8.1094.81 16.4863 1.646 22.64-5.7129l.0029-.0039c.6044387-.7534187.8533533-1.7315007.6826-2.6822-.0899994-.4592259-.3932698-.8481635-.8167-1.0474zm-42.0381 29.7446c-.1201754.2157725-.3219209.3742868-.56.44l-1.0684.2983c-.4949157.1376357-1.0078362-.1513714-1.1465-.646l-12.2095-43.6895c-.20840349-.7523825.23089143-1.5316224.9825-1.7428l.1367-.0381c.12366014-.0348192.25153137-.0524183.38-.0523.63429117.0010181 1.19083557.4229483 1.3631 1.0334l.1083.3876v.0021l6.2529 22.3755 5.8468 20.9238c.0669515.2380103.0360256.4929057-.0859.708zm40.6329-27.2925c-5.4736 6.5459-12.9707 5.7974-20.9043 5.0039-7.9033-.79-16.06-1.605-22.1552 5.1558l-5.463-19.548-2.0643-7.3873c5.5068-7.0794 13.1796-6.3119 21.3045-5.5007 7.7148.7695 15.6787 1.5664 21.7373-4.7095-.7467138 5.70010904-1.859683 11.3462228-3.332 16.9033-.1993066.7185155.0267229 1.4878686.583 1.9844 3.1786296 2.9100325 6.7366511 5.3762694 10.5771 7.3315-.0213812.2768572-.1194065.5422977-.2831.7666z"/></g></g></svg>'
    };

    const icons = {
        search: '<svg style="fill: #646772;" version="1.1" width="17" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 487.95 487.95" style="enable-background:new 0 0 487.95 487.95;" xml:space="preserve"> <g> <g> <path d="M481.8,453l-140-140.1c27.6-33.1,44.2-75.4,44.2-121.6C386,85.9,299.5,0.2,193.1,0.2S0,86,0,191.4s86.5,191.1,192.9,191.1 c45.2,0,86.8-15.5,119.8-41.4l140.5,140.5c8.2,8.2,20.4,8.2,28.6,0C490,473.4,490,461.2,481.8,453z M41,191.4 c0-82.8,68.2-150.1,151.9-150.1s151.9,67.3,151.9,150.1s-68.2,150.1-151.9,150.1S41,274.1,41,191.4z"/> </g> </g> <g> </g> <g> </g> </svg>',
        close: '<svg style="height: 11px !important;" viewBox="0 0 52 52" xmlns="http://www.w3.org/2000/svg"><path d="M28.94,26,51.39,3.55A2.08,2.08,0,0,0,48.45.61L26,23.06,3.55.61A2.08,2.08,0,0,0,.61,3.55L23.06,26,.61,48.45A2.08,2.08,0,0,0,2.08,52a2.05,2.05,0,0,0,1.47-.61L26,28.94,48.45,51.39a2.08,2.08,0,0,0,2.94-2.94Z"/></svg>',
        move: '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512.006 512.006" xml:space="preserve"> <g> <g> <path d="M508.247,246.756l-72.457-72.465c-5.009-5.009-13.107-5.009-18.116,0c-5.009,5.009-5.009,13.107,0,18.116l50.594,50.594 H268.811V43.748l50.594,50.594c5.009,5.009,13.107,5.009,18.116,0c5.009-5.009,5.009-13.107,0-18.116L265.056,3.761 c-5.001-5.009-13.107-5.009-18.116,0l-72.457,72.457c-5.009,5.009-5.009,13.107,0,18.116c5.001,5.009,13.107,5.009,18.116,0 l50.594-50.594v199.27H43.744l50.594-50.594c5.009-5.009,5.009-13.107,0-18.116c-5.009-5.009-13.107-5.009-18.116,0L3.757,246.756 c-5.009,5.001-5.009,13.107,0,18.116l72.465,72.457c5.009,5.009,13.107,5.009,18.116,0c5.009-5.001,5.009-13.107,0-18.116 l-50.594-50.594h199.458v199.646l-50.594-50.594c-5.009-5.001-13.107-5.001-18.116,0c-5.009,5.009-5.009,13.107,0,18.116 l72.457,72.465c5,5,13.107,5,18.116,0l72.465-72.457c5.009-5.009,5.009-13.107,0-18.116c-5.009-5-13.107-5-18.116,0 l-50.594,50.594V268.627h199.458l-50.594,50.594c-5.009,5.009-5.009,13.107,0,18.116s13.107,5.009,18.116,0l72.465-72.457 C513.257,259.872,513.257,251.765,508.247,246.756z"/> </g> </g> <g> </g> </svg>'
    }




    // Translated category titles are handed in via the l10n option, the keys are used as fallback
    const categoryTitle = key => (this.options.l10n && this.options.l10n[key]) || key;

    // Emojis that are modifiable by a skin tone follow the rule: put the modifier behind every base character
    const skinToneBases = new Set([0x261D, 0x26F9, 0x270A, 0x270B, 0x270C, 0x270D, 0x1F385, 0x1F3C2, 0x1F3C3, 0x1F3C4, 0x1F3C7, 0x1F3CA, 0x1F3CB, 0x1F3CC, 0x1F442, 0x1F443, 0x1F446, 0x1F447, 0x1F448, 0x1F449, 0x1F44A, 0x1F44B, 0x1F44C, 0x1F44D, 0x1F44E, 0x1F44F, 0x1F450, 0x1F466, 0x1F467, 0x1F468, 0x1F469, 0x1F46B, 0x1F46C, 0x1F46D, 0x1F46E, 0x1F46F, 0x1F470, 0x1F471, 0x1F472, 0x1F473, 0x1F474, 0x1F475, 0x1F476, 0x1F477, 0x1F478, 0x1F47C, 0x1F481, 0x1F482, 0x1F483, 0x1F485, 0x1F486, 0x1F487, 0x1F48F, 0x1F491, 0x1F4AA, 0x1F574, 0x1F575, 0x1F57A, 0x1F590, 0x1F595, 0x1F596, 0x1F645, 0x1F646, 0x1F647, 0x1F64B, 0x1F64C, 0x1F64D, 0x1F64E, 0x1F64F, 0x1F6A3, 0x1F6B4, 0x1F6B5, 0x1F6B6, 0x1F6C0, 0x1F6CC, 0x1F90C, 0x1F90F, 0x1F918, 0x1F919, 0x1F91A, 0x1F91B, 0x1F91C, 0x1F91D, 0x1F91E, 0x1F91F, 0x1F926, 0x1F930, 0x1F931, 0x1F932, 0x1F933, 0x1F934, 0x1F935, 0x1F936, 0x1F937, 0x1F938, 0x1F939, 0x1F93C, 0x1F93D, 0x1F93E, 0x1F977, 0x1F9B5, 0x1F9B6, 0x1F9B8, 0x1F9B9, 0x1F9BB, 0x1F9CD, 0x1F9CE, 0x1F9CF, 0x1F9D1, 0x1F9D2, 0x1F9D3, 0x1F9D4, 0x1F9D5, 0x1F9D6, 0x1F9D7, 0x1F9D8, 0x1F9D9, 0x1F9DA, 0x1F9DB, 0x1F9DC, 0x1F9DD, 0x1FAC3, 0x1FAC4, 0x1FAC5, 0x1FAF0, 0x1FAF1, 0x1FAF2, 0x1FAF3, 0x1FAF4, 0x1FAF5, 0x1FAF6, 0x1FAF7, 0x1FAF8, 0x1FAF9, 0x1FAFA]);
    const skinToneModifiers = ['', '\u{1F3FB}', '\u{1F3FC}', '\u{1F3FD}', '\u{1F3FE}', '\u{1F3FF}'];
    const skinToneStorageKey = 'friendica.emojipicker.skintone';

    const frequentStorageKey = 'friendica.emojipicker.usage';
    const frequentMaxItems = 16;
    const frequentMaxTotal = 200;

    const functions = {

        // Emojis of recent Unicode versions ("v" in the data) are only shown if the system can draw them.
        // An unsupported emoji is drawn as an empty box (gray) or as its parts (wider), a supported one is a single coloured glyph.
        supportCache: {},

        isSupported: (emoji, version) => {
            if (!version) {
                return true;
            }

            if (functions.supportCache[emoji] !== undefined) {
                return functions.supportCache[emoji];
            }

            let supported = true;
            try {
                const size = 32;
                const canvas = document.createElement('canvas');
                canvas.width = size * 3;
                canvas.height = size * 2;
                const context = canvas.getContext('2d', {willReadFrequently: true});
                context.textBaseline = 'top';
                context.font = size + 'px sans-serif';

                // Sequences like the ballet dancer fall apart into several glyphs when unsupported
                const parts = Array.from(emoji).filter(part => part !== '\u200D' && part !== '\uFE0F');
                const singleWidth = context.measureText(parts[0]).width;
                let coloured = false;
                if (context.measureText(emoji).width < singleWidth * 1.5) {
                    context.fillText(emoji, 0, 0);
                    const pixels = context.getImageData(0, 0, canvas.width, canvas.height).data;
                    for (let i = 0; i < pixels.length; i += 4) {
                        if (pixels[i + 3] > 0 && (pixels[i] !== pixels[i + 1] || pixels[i + 1] !== pixels[i + 2])) {
                            coloured = true;
                            break;
                        }
                    }
                }
                supported = coloured;
            } catch (e) {
                // Canvas not available, show the emoji and hope for the best
            }

            functions.supportCache[emoji] = supported;
            return supported;
        },

        loadUsage: () => {
            try {
                const usage = JSON.parse(window.localStorage.getItem(frequentStorageKey));
                return (usage && typeof usage === 'object') ? usage : {};
            } catch (e) {
                return {};
            }
        },

        recordUsage: (emoji) => {
            try {
                const usage = functions.loadUsage();

                // Let old favourites fade out by halving all counters once the total gets too high.
                // This happens before counting the new use, so the emoji just picked can't fade out itself.
                let total = 1;
                Object.keys(usage).forEach(key => total += usage[key].c);
                if (total > frequentMaxTotal) {
                    Object.keys(usage).forEach(key => {
                        usage[key].c = Math.floor(usage[key].c / 2);
                        if (usage[key].c < 1) {
                            delete usage[key];
                        }
                    });
                }

                usage[emoji] = {c: ((usage[emoji] && usage[emoji].c) || 0) + 1, t: Date.now()};

                window.localStorage.setItem(frequentStorageKey, JSON.stringify(usage));
            } catch (e) {
                // Storage not available, the frequently used emojis are just not remembered
            }
        },

        loadSkinTone: () => {
            try {
                const tone = parseInt(window.localStorage.getItem(skinToneStorageKey), 10);
                return (tone >= 0 && tone < skinToneModifiers.length) ? tone : 0;
            } catch (e) {
                return 0;
            }
        },

        saveSkinTone: (tone) => {
            try {
                window.localStorage.setItem(skinToneStorageKey, tone);
            } catch (e) {
                // Storage not available, the skin tone is just not remembered
            }
        },

        applyTone: (emoji, tone) => {
            if (!tone) {
                return emoji;
            }

            const codePoints = Array.from(emoji);
            let result = '';
            for (let i = 0; i < codePoints.length; i++) {
                result += codePoints[i];
                // A base between two joiners (people holding hands) is not modified, only the people around it
                const joined = codePoints[i - 1] === '\u200D' && codePoints[i + 1] === '\u200D';
                if (skinToneBases.has(codePoints[i].codePointAt(0)) && !joined) {
                    result += skinToneModifiers[tone];
                    // The modifier replaces the emoji presentation selector
                    if (codePoints[i + 1] === '\uFE0F') {
                        i++;
                    }
                }
            }
            return result;
        },

        updateTones: () => {
            const tone = functions.loadSkinTone();
            document.querySelectorAll('.fg-emoji-list a[data-base]').forEach(a => {
                a.textContent = functions.applyTone(a.getAttribute('data-base'), tone);
            });
        },

        skinToneChange: (e) => {
            functions.saveSkinTone(e.target.value);
            functions.updateTones();
        },

        frequentEmojis: () => {
            const titles = {};
            for (const key in emojiObj) {
                emojiObj[key].forEach(ej => titles[ej.emoji] = ej);
            }

            const usage = functions.loadUsage();
            return Object.keys(usage)
                .filter(emoji => titles[emoji] && usage[emoji].c > 0 && functions.isSupported(emoji, titles[emoji].v))
                .sort((a, b) => (usage[b].c - usage[a].c) || (usage[b].t - usage[a].t))
                .slice(0, frequentMaxItems)
                .map(emoji => ({emoji: emoji, title: titles[emoji].title, tone: titles[emoji].tone}));
        },

        styles: () => {

            const styles = `
                <style>
                    .fg-emoji-container {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: ${pickerWidth}px;
                        height: ${pickerHeight}px;
                        border-radius: 5px;
                        box-shadow: 0px 3px 20px 0px rgba(0, 0, 0, 0.62);
                        background-color: white;
                        overflow: hidden;
                        z-index: 9999;
                    }

                    .fg-emoji-container svg {
                        max-width: 100%;
                        box-sizing: border-box;
                        width: 15px;
                        height: 15px;
                    }

                    .fg-emoji-picker-category-title {
                        display: block;
                        margin: 20px 0 0 0;
                        padding: 0 10px 5px 10px;
                        font-size: 16px;
                        font-family: sans-serif;
                        font-weight: bold;
                        flex: 0 0 calc(100% - 20px);
                        border-bottom: 1px solid #ededed;
                    }

                    .fg-emoji-nav {
                        background-color: #646772;
                    }

                    .fg-emoji-nav li a svg {
                        transition: all .2s ease;
                        fill: white;
                    }

                    .fg-emoji-nav li:hover a svg {
                        fill: black;
                    }

                    .fg-emoji-nav ul {
                        display: flex;
                        flex-wrap: wrap;
                        list-style: none;
                        margin: 0;
                        padding: 0;
                        border-bottom: 1px solid #dbdbdb;
                    }

                    .fg-emoji-nav ul li {
                        flex: 1;
                    }

                    .fg-emoji-nav ul li a {
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        height: 40px;
                        transition: all .2s ease;
                    }

                    .fg-emoji-nav ul li a:hover {
                        background-color: #e9ebf1;
                    }

                    .fg-emoji-nav ul li.active a {
                        background-color: #e9ebf1;
                    }

                    .fg-emoji-nav ul li.emoji-picker-nav-active a {
                        background-color: #e9ebf1;
                    }

                    .fg-emoji-nav ul li.emoji-picker-nav-active a svg {
                        fill: #646772;
                    }

                    .fg-emoji-picker-move {
                        /* pointer-events: none; */
                        cursor: move;
                    }

                    .fg-picker-special-buttons a {
                        background-color: ${this.options.specialButtons ? this.options.specialButtons : '#ed5e28'};
                    }

                    .fg-picker-special-buttons:last-child a {
                        box-shadow: inset 1px 0px 0px 0 rgba(0, 0, 0, 0.11);
                    }

                    .fg-emoji-list {
                        list-style: none;
                        margin: 0;
                        padding: 0;
                        overflow-y: scroll;
                        overflow-x: hidden;
                        height: 323px;
                    }

                    .fg-emoji-picker-category-wrapper,
                    .fg-emoji-picker-frequent {
                        display: flex;
                        flex-wrap: wrap;
                        flex: 1;
                    }

                    .fg-emoji-list li {
                        position: relative;
                        display: flex;
                        flex-wrap: wrap;
                        justify-content: center;
                        align-items: center;
                        flex: 0 0 calc(100% / 6);
                        height: 50px;
                    }

                    .fg-emoji-list li a {
                        position: absolute;
                        width: 100%;
                        height: 100%;
                        text-decoration: none;
                        display: flex;
                        flex-wrap: wrap;
                        justify-content: center;
                        align-items: center;
                        font-size: 23px;
                        background-color: #ffffff;
                        border-radius: 3px;
                        transition: all .3s ease;
                    }
                    
                    .fg-emoji-list li a:hover {
                        background-color: #ebebeb;
                    }

                    .fg-emoji-picker-search {
                        position: relative;
                    }

                    .fg-emoji-picker-search input {
                        border: none;
                        box-shadow: 0 0 0 0;
                        outline: none;
                        box-sizing: border-box;
                        width: 100%;
                        display: block;
                        padding: 10px 100px 10px 15px;
                        background-color: #f3f3f3;
                    }

                    .fg-emoji-picker-search .fg-emoji-picker-skintone {
                        position: absolute;
                        right: 5px;
                        top: 50%;
                        transform: translateY(-50%);
                        width: auto;
                        height: auto;
                        padding: 0 2px;
                        border: none;
                        outline: none;
                        background-color: transparent;
                        cursor: pointer;
                    }

                    .fg-emoji-picker-search .fg-emoji-picker-search-icon {
                        position: absolute;
                        right: 50px;
                        top: 0;
                        width: 40px;
                        height: 100%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }

                </style>
            `;

            document.head.insertAdjacentHTML('beforeend', styles);
        },


        position: () => {

            const e             = window.event;
            const clickPosX     = e.clientX;
            const clickPosY     = e.clientY;
            const obj           = {};

            obj.left            = clickPosX;
            obj.top             = clickPosY;

            return obj;

        },


        rePositioning: (picker) => {
            picker.getBoundingClientRect().right > window.screen.availWidth ? picker.style.left = window.screen.availWidth - picker.offsetWidth + 'px' : false;
            
            if (window.innerHeight > pickerHeight) {
                picker.getBoundingClientRect().bottom > window.innerHeight ? picker.style.top = window.innerHeight - picker.offsetHeight + 'px' : false;
            }
        },

        
        render: (e, attr) => {
            // attr is empty in friendica, no idea why..
            if (!attr) attr='.emojis'
            emojiList = undefined;
            const index = this.options.trigger.findIndex(item => item.selector === attr);
            this.insertInto = this.options.trigger[index].insertInto;

            const insertSelector = Array.isArray(this.insertInto) ? this.insertInto.join(',') : this.insertInto;
            const triggerElement = e.target && e.target.closest(attr);

            if (triggerElement) {
                const targetCandidates = Array.from(document.querySelectorAll(insertSelector))
                    .filter(field => (field.tagName === 'TEXTAREA') && !field.closest('.fg-emoji-container'));

                if (targetCandidates.length) {
                    const triggerForm = triggerElement.closest('form');
                    const formMatch = triggerForm && targetCandidates.find(field => triggerForm.contains(field));

                    if (formMatch) {
                        activeInsertTarget = formMatch;
                    } else {
                        // Fall back to the currently focused textarea or the first candidate.
                        const focusedMatch = targetCandidates.find(field => field === document.activeElement);
                        activeInsertTarget = focusedMatch || targetCandidates[0];
                    }
                }
            }

            const position = functions.position();

            if (!emojiesHTML.length) {

                for (const key in emojiObj) {
                    if (emojiObj.hasOwnProperty.call(emojiObj, key)) {
                        const categoryObj = emojiObj[key];

                        
                        categoriesHTML += `<li>
                            <a title="${categoryTitle(key)}" href="#${key}">${categoryFlags[key]}</a>
                        </li>`;

                        emojiesHTML += `<div class="fg-emoji-picker-category-wrapper" id="${key}">`;
                            emojiesHTML += `<p class="fg-emoji-picker-category-title">${categoryTitle(key)}</p>`;
                            categoryObj.filter(ej => functions.isSupported(ej.emoji, ej.v)).forEach(ej => {
                                emojiesHTML += `<li data-title="${ej.title.toLowerCase()}">
                                    <a title="${ej.title}" href="#"${ej.tone ? ` data-base="${ej.emoji}"` : ''}>${ej.emoji}</a>
                                </li>`;
                            });
                        emojiesHTML += '</div>';
                    }
                }
            }


            if (document.querySelector('.fg-emoji-container')) {
                this.lib('.fg-emoji-container').remove();
            }


            // The frequently used emojis change with every use, so they are not part of the cached HTML
            let frequentNavHTML = '';
            let frequentHTML = '';
            const frequent = functions.frequentEmojis();
            if (frequent.length) {
                const frequentTitle = categoryTitle('Frequent');
                frequentNavHTML = `<li>
                    <a title="${frequentTitle}" href="#Frequent">${categoryFlags['Frequent']}</a>
                </li>`;
                frequentHTML = `<div class="fg-emoji-picker-frequent" id="Frequent">`;
                    frequentHTML += `<p class="fg-emoji-picker-category-title">${frequentTitle}</p>`;
                    frequent.forEach(ej => {
                        frequentHTML += `<li>
                            <a title="${ej.title}" href="#"${ej.tone ? ` data-base="${ej.emoji}"` : ''}>${ej.emoji}</a>
                        </li>`;
                    });
                frequentHTML += '</div>';
            }

            const picker = `
                <div class="fg-emoji-container" style="left: ${position.left}px; top: ${position.top}px;">
                    <nav class="fg-emoji-nav">
                        <ul>
                            ${frequentNavHTML}
                            ${categoriesHTML}

                            <li class="fg-picker-special-buttons" id="fg-emoji-picker-move"><a class="fg-emoji-picker-move" href="#">${icons.move}</a></li>
                            ${this.options.closeButton ? `<li class="fg-picker-special-buttons"><a id="fg-emoji-picker-close-button" href="#">`+icons.close+`</a></li>` : ''}
                        </ul>
                    </nav>

                    <div class="fg-emoji-picker-search">
                        <input type="text" placeholder="${(this.options.l10n && this.options.l10n.search) || 'Search'}" autofocus />
                        
                        <select class="fg-emoji-picker-skintone" title="${categoryTitle('skintone')}" aria-label="${categoryTitle('skintone')}">
                            ${skinToneModifiers.map((modifier, tone) => `<option value="${tone}">\u{1F44B}${modifier}</option>`).join('')}
                        </select>
                        <span class="fg-emoji-picker-search-icon">${icons.search}</sapn>
                    </div>

                    <div>
                        <!--<div class="fg-emoji-picker-loader-animation">
                            <div class="spinner">
                                <div class="bounce1"></div>
                                <div class="bounce2"></div>
                                <div class="bounce3"></div>
                            </div>
                        </div>-->

                        <ul class="fg-emoji-list">
                            ${frequentHTML}
                            ${emojiesHTML}
                        </ul>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', picker);

            document.querySelector('.fg-emoji-picker-skintone').value = functions.loadSkinTone();
            functions.updateTones();

            functions.rePositioning(document.querySelector('.fg-emoji-container'));

            setTimeout(() => {
                document.querySelector('.fg-emoji-picker-search input').focus();
            }, 500)
        },


        closePicker: (e) => {

            e.preventDefault();

            this.lib('.fg-emoji-container').remove();

            moseMove = false;
        },


        checkPickerExist(e) {

            if (document.querySelector('.fg-emoji-container') && !e.target.closest('.fg-emoji-container') && !moseMove) {

                functions.closePicker.call(this, e);
            }
        },


        setCaretPosition: (field, caretPos) => {
            var elem = field
            if (elem != null) {
                if (elem.createTextRange) {
                    var range = elem.createTextRange();
                    range.move('character', caretPos);
                    range.select();
                } else {
                    if (elem.selectionStart) {
                        elem.focus();
                        elem.setSelectionRange(caretPos, caretPos);
                    } else {
                        elem.focus();
                    }
                }
            }
        },


        insert: e => {

            e.preventDefault();
            
            const emoji = e.target.innerText.trim();
            // Skin tone variants are counted as their base emoji, so that they follow the selected skin tone
            functions.recordUsage(e.target.getAttribute('data-base') || emoji);
            const insertSelector = Array.isArray(this.insertInto) ? this.insertInto.join(',') : this.insertInto;
            const myFields = Array.from(document.querySelectorAll(insertSelector));
            const myValue = emoji;
            const focusedField = myFields.find(field => field === document.activeElement);
            const targetFields = (activeInsertTarget && myFields.includes(activeInsertTarget)) ? [activeInsertTarget] : (focusedField ? [focusedField] : myFields.slice(0, 1));

            if (!targetFields.length) {
                return;
            }

            // Check if selector is an array
            targetFields.forEach(myField => {

                if (document.selection) {
                    myField.focus();
                    sel = document.selection.createRange();
                    sel.text = myValue;
                } else if (myField.selectionStart || myField.selectionStart == "0") {
                    const startPos = myField.selectionStart;
                    const endPos = myField.selectionEnd;
                    myField.value = myField.value.substring(0, startPos) + myValue + myField.value.substring(endPos, myField.value.length);
                    
                    functions.setCaretPosition(myField, startPos + myValue.length)
                    
                } else {
                    myField.value += myValue;
                    myField.focus()
                }

            })
        },


        categoryNav: e => {
            e.preventDefault();

            const link          = e.target.closest('a');

            if (link.getAttribute('id') && link.getAttribute('id') === 'fg-emoji-picker-close-button') return false;
            if (link.className.includes('fg-emoji-picker-move')) return false;

            const id            = link.getAttribute('href');
            const emojiBody     = document.querySelector('.fg-emoji-list');
            const destination   = emojiBody.querySelector(`${id}`);

            this.lib('.fg-emoji-nav li').removeClass('emoji-picker-nav-active');
            link.closest('li').classList.add('emoji-picker-nav-active');

            destination.scrollIntoView({behavior: "smooth", block: "start", inline: "nearest"})
        },


        search: e => {

            const val = e.target.value.trim();

            // The frequently used emojis would only duplicate the search results
            const frequentSection = document.querySelector('.fg-emoji-picker-frequent');
            if (frequentSection) {
                frequentSection.style.display = val ? 'none' : '';
            }

            if (!emojiList) {
                emojiList = Array.from(document.querySelectorAll('.fg-emoji-picker-category-wrapper li'));
            }

            emojiList.filter(emoji => {
                if (!emoji.getAttribute('data-title').match(val)) {
                    emoji.style.display = 'none'
                } else {
                    emoji.style.display = ''
                }
            })
        },


        mouseDown: e => {
            e.preventDefault();
            moseMove = true;
        },

        mouseUp: e => {
            e.preventDefault();
            moseMove = false;
        },

        mouseMove: e => {

            if (moseMove) {
                e.preventDefault();
                const el = document.querySelector('.fg-emoji-container');
                el.style.left = e.clientX - 320 + 'px';
                el.style.top = e.clientY - 10 + 'px';
            }
        }
    };



    const bindEvents = () => {

        this.lib(document.body).on('click', functions.closePicker, '#fg-emoji-picker-close-button');
        this.lib(document.body).on('click', functions.checkPickerExist);
        this.lib(document.body).on('click', functions.render, this.trigger);
        this.lib(document).on('focusin', e => {
            // Ignore focus changes inside the emoji picker (e.g. search input).
            if (!e.target || e.target.closest('.fg-emoji-container')) {
                return;
            }

            if (e.target.matches('textarea')) {
                activeInsertTarget = e.target;
            }
        });
        this.lib(document.body).on('click', functions.insert, '.fg-emoji-list a');
        this.lib(document.body).on('click', functions.categoryNav, '.fg-emoji-nav a');
        this.lib(document.body).on('input', functions.search, '.fg-emoji-picker-search input');
        this.lib(document.body).on('change', functions.skinToneChange, '.fg-emoji-picker-skintone');
        this.lib(document).on('mousedown', functions.mouseDown, '#fg-emoji-picker-move');
        this.lib(document).on('mouseup', functions.mouseUp, '#fg-emoji-picker-move');
        this.lib(document).on('mousemove', functions.mouseMove);
    };

    

    (() => {

        // Start styles
        functions.styles();

        // Event functions
        bindEvents.call(this);
        
    })()
}