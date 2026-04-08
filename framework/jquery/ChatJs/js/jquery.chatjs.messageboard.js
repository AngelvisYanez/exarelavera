var MessageBoardOptions = (function () {
    function MessageBoardOptions() {
    }
    return MessageBoardOptions;
})();

var MessageBoard = (function () {
    function MessageBoard(jQuery, options) {
        var _this = this;
        this.$el = jQuery;

        var defaultOptions = new MessageBoardOptions();
        defaultOptions.typingText = " esta escribiendo...";
        defaultOptions.playSound = true;
        defaultOptions.height = 100;
        defaultOptions.chatJsContentPath = "/chatjs/";
        defaultOptions.newMessage = function (message) {
        };

        this.options = $.extend({}, defaultOptions, options);

        this.$el.addClass("message-board");

        ChatJsUtils.setOuterHeight(this.$el, this.options.height);

        this.$messagesWrapper = $("<div/>").addClass("messages-wrapper").appendTo(this.$el);

        // sets up the text
        var $windowTextBoxWrapper = $("<div/>").addClass("chat-window-text-box-wrapper").appendTo(this.$el);

        this.$textBox = $("<textarea />").attr("rows", "1").addClass("chat-window-text-box").appendTo($windowTextBoxWrapper);

        this.$textBox.autosize({
            callback: function (ta) {
                var messagesHeight = _this.options.height - $(ta).outerHeight();
                ChatJsUtils.setOuterHeight(_this.$messagesWrapper, messagesHeight);
            }
        });

        this.$textBox.val(this.$textBox.val());

        this.options.adapter.client.onTypingSignalReceived(function (typingSignal) {
            var shouldProcessTypingSignal = false;

            if (_this.options.otherUserId) {
                // it's a PM message board.
                shouldProcessTypingSignal = typingSignal.UserToId === _this.options.userId && typingSignal.UserFrom.Id === _this.options.otherUserId;
            } else if (_this.options.roomId) {
                // it's a room message board
                shouldProcessTypingSignal = typingSignal.RoomId === _this.options.roomId && typingSignal.UserFrom.Id !== _this.options.userId;
            } else if (_this.options.conversationId) {
                // it's a conversation message board
                shouldProcessTypingSignal = typingSignal.ConversationId === _this.options.conversationId && typingSignal.UserFrom.Id !== _this.options.userId;
            }
            if (shouldProcessTypingSignal)
                _this.showTypingSignal(typingSignal.UserFrom);
        });       
        this.options.adapter.client.onMessagesChanged(function (message) {
            var shouldProcessMessage = false;

            if (_this.options.otherUserId) {
                // it's a PM message board.
                shouldProcessMessage = (message.UserFromId === _this.options.userId && message.UserToId === _this.options.otherUserId) || (message.UserFromId === _this.options.otherUserId && message.UserToId === _this.options.userId);
            } else if (_this.options.roomId) {
                // it's a room message board
                shouldProcessMessage = message.RoomId === _this.options.roomId;
            } else if (_this.options.conversationId) {
                // it's a conversation message board
                shouldProcessMessage = message.ConversationId === _this.options.conversationId;
            }

            if (shouldProcessMessage) {
                _this.addMessage(message);
                if (message.UserFromId !== _this.options.userId) {
                    if (_this.options.playSound)
                        _this.playSound();                        
                        _this.$el.parent().parent().find('.chat-window-title').removeClass('decored').addClass('chat-alert');
                        _this.removeTypingSignal();
                }
                _this.options.newMessage(message);
            }
        },_this.options.otherUserId);

        // gets the message history
        this.options.adapter.server.getMessageHistory(this.options.roomId, this.options.conversationId, this.options.otherUserId, function (messages) {
            for (var i = 0; i < messages.length; i++) {
                _this.addMessage(messages[i], null, false);
            }

            _this.adjustScroll();
            _this.$textBox.focus(function (e) {
                if(_this.$el.parent().parent().find('.chat-window-title').hasClass('chat-alert'))
                    _this.$el.parent().parent().find('.chat-window-title').removeClass('chat-alert').addClass('decored');
            });
            _this.$textBox.keypress(function (e) {
                // if a send typing signal is in course, remove it and create another
                if (_this.sendTypingSignalTimeout === undefined) {
                    _this.sendTypingSignalTimeout = setTimeout(function () {
                        _this.sendTypingSignalTimeout = undefined;
                    }, 3000);
                    _this.sendTypingSignal();
                }
                
                if(_this.$el.parent().parent().find('.chat-window-title').hasClass('chat-alert'))
                    _this.$el.parent().parent().find('.chat-window-title').removeClass('chat-alert').addClass('decored');
                
                if (e.which === 13) {
                    e.preventDefault();
                    if (_this.$textBox.val()) {
                        _this.sendMessage(_this.$textBox.val());
                        _this.$textBox.val('').trigger("autosize.resize");
                    }
                }
            });
        });
    }
    MessageBoard.prototype.showTypingSignal = function (user) {
        var _this = this;
        /// <summary>Adds a typing signal to this window. It means the other user is typing</summary>
        /// <param FullName="user" type="Object">the other user info</param>
        if (this.$typingSignal)
            this.$typingSignal.remove();
        this.$typingSignal = $("<p/>").addClass("typing-signal").html('<b>'+user.Name+'</b>' + this.options.typingText);
        this.$messagesWrapper.append(this.$typingSignal);
        if (this.typingSignalTimeout)
            clearTimeout(this.typingSignalTimeout);
        this.typingSignalTimeout = setTimeout(function () {
            _this.removeTypingSignal();
        }, 5000);

        this.adjustScroll();
    };

    MessageBoard.prototype.removeTypingSignal = function () {
        /// <summary>Remove the typing signal, if it exists</summary>
        if (this.$typingSignal)
            this.$typingSignal.remove();
        if (this.typingSignalTimeout)
            clearTimeout(this.typingSignalTimeout);
    };

    MessageBoard.prototype.adjustScroll = function () {
        this.$messagesWrapper[0].scrollTop = this.$messagesWrapper[0].scrollHeight;
    };

    MessageBoard.prototype.sendTypingSignal = function () {
        /// <summary>Sends a typing signal to the other user</summary>
        this.options.adapter.server.sendTypingSignal(this.options.roomId, this.options.conversationId, this.options.otherUserId, function () {
        });
    };

    MessageBoard.prototype.sendMessage = function (messageText) {
        /// <summary>Sends a message to the other user</summary>
        /// <param FullName="messageText" type="String">Message being sent</param>
        var generateGuidPart = function () {
            return (((1 + Math.random()) * 0x10000) | 0).toString(16).substring(1);
        };

        var clientGuid = (generateGuidPart() + generateGuidPart() + '-' + generateGuidPart() + '-' + generateGuidPart() + '-' + generateGuidPart() + '-' + generateGuidPart() + generateGuidPart() + generateGuidPart());

        var message = new ChatMessageInfo();
        message.UserFromId = this.options.userId;
        message.Message = messageText;

        this.addMessage(message, clientGuid);

        this.options.adapter.server.sendMessage(this.options.roomId, this.options.conversationId, this.options.otherUserId, messageText, clientGuid, function () {
        });
    };

    MessageBoard.prototype.playSound = function () {
        /// <summary>Plays a notification sound</summary>
        /// <param FullName="fileFullName" type="String">The file path without extension</param>
        var $soundContainer = $("#soundContainer");
        if (!$soundContainer.length)
            $soundContainer = $("<div>").attr("id", "soundContainer").appendTo($("body"));
        var baseFileName = this.options.chatJsContentPath + "sounds/chat";
        var oggFileName = baseFileName + ".ogg";
        var mp3FileName = baseFileName + ".mp3";

        var $audioTag = $("<audio/>").attr("autoplay", "autoplay");
        $("<source/>").attr("src", oggFileName).attr("type", "audio/mpeg").appendTo($audioTag);
        $("<embed/>").attr("src", mp3FileName).attr("autostart", "true").attr("loop", "false").appendTo($audioTag);

        $audioTag.appendTo($soundContainer);
    };

    MessageBoard.prototype.focus = function () {
        this.$textBox.focus();
    };

    MessageBoard.prototype.addMessage = function (message, clientGuid, scroll) {
        /// <summary>
        ///     Adds a message to the board. This method is called both when the current user or the other user is sending a
        ///     message
        /// </summary>
        /// <param name="message" type="Object">Message</param>
        /// <param name="clientGuid" type="String">Message client guid</param>
        var messageClass='right';
        if (scroll === undefined)
            scroll = true;

        if (message.UserFromId !== this.options.userId) {
            // the message did not came from myself. Better erase the typing signal
            this.removeTypingSignal();
            messageClass='left';
        }

        // takes a jQuery element and replace it's content that seems like an URL with an
        // actual link or e-mail
        function linkify($element) {
            var inputText = $element.html();
            var replacedText, replacePattern1, replacePattern2, replacePattern3;

            //URLs starting with http://, https://, or ftp://
            replacePattern1 = /(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/gim;
            replacedText = inputText.replace(replacePattern1, '<a href="$1" target="_blank">$1</a>');

            //URLs starting with "www." (without // before it, or it'd re-link the ones done above).
            replacePattern2 = /(^|[^\/])(www\.[\S]+(\b|$))/gim;
            replacedText = replacedText.replace(replacePattern2, '$1<a href="http://$2" target="_blank">$2</a>');

            //Change email addresses to mailto:: links.
            replacePattern3 = /(\w+@[a-zA-Z_]+?\.[a-zA-Z]{2,6})/gim;
            replacedText = replacedText.replace(replacePattern3, '<a href="mailto:$1">$1</a>');

            return $element.html(replacedText);
        }

        function emotify($element) {
            var inputText = $element.html();
            var replacedText = inputText;

            var emoticons = [
                { pattern: /:-\)/g, cssClass: "happy" },
                { pattern: /:\)/g, cssClass: "happy" },
                { pattern: /=\)/g, cssClass: "happy" },
                { pattern: /:-D/g, cssClass: "very-happy" },
                { pattern: /:D/g, cssClass: "very-happy" },
                { pattern: /=D/g, cssClass: "very-happy" },
                { pattern: /:-\(/g, cssClass: "sad" },
                { pattern: /:\(/g, cssClass: "sad" },
                { pattern: /=\(/g, cssClass: "sad" },
                { pattern: /:-\|/g, cssClass: "wary" },
                { pattern: /:\|/g, cssClass: "wary" },
                { pattern: /=\|/g, cssClass: "wary" },
                { pattern: /:-O/g, cssClass: "astonished" },
                { pattern: /:O/g, cssClass: "astonished" },
                { pattern: /=O/g, cssClass: "astonished" },
                { pattern: /:-P/g, cssClass: "tongue" },
                { pattern: /:P/g, cssClass: "tongue" },
                { pattern: /=P/g, cssClass: "tongue" }
            ];            
            for (var i = 0; i < emoticons.length; i++) {
                replacedText = replacedText.replace(emoticons[i].pattern, "<span class='" + emoticons[i].cssClass + "'></span>");
            }

            return $element.html(replacedText);
        }

        if (message.ClientGuid && $("p[data-val-client-guid='" + message.ClientGuid + "']").length) {
            // in this case, this message is comming from the server AND the current user POSTED the message.
            // so he/she already has this message in the list. We DO NOT need to add the message.
            $("p[data-val-client-guid='" + message.ClientGuid + "']").removeClass("temp-message").addClass("sended").removeAttr("data-val-client-guid");
        } else {
            var d = new Date, sended_date = [d.getFullYear(),(d.getMonth()+1).padLeft(),d.getDate().padLeft()].join('-')+' '+[d.getHours().padLeft(),d.getMinutes().padLeft()].join(':');
            if(typeof message.Sended!=='undefined') sended_date=message.Sended.substring(0,16);
            var $messageP = $("<p/>").text(message.Message).attr('title',sended_date);
            if (clientGuid)
                $messageP.attr("data-val-client-guid", clientGuid).addClass("temp-message");

            linkify($messageP);
            emotify($messageP);

            // gets the last message to see if it's possible to just append the text
            var $lastMessage = $("div.chat-message:last", this.$messagesWrapper);
            /* AGREGADO PARA CONTROL DE FECHA Y MINUTOS */
            var lastDay = $("div.chat-date:last", this.$messagesWrapper).text(),newMsg=false,newMsgTime;
            if(lastDay!==sended_date.substring(0,10)){
                $('<div class="chat-date">'+sended_date.substring(0,10)+'</div>').appendTo(this.$messagesWrapper);
                newMsg=true;
            }
            if($lastMessage.length && $lastMessage.attr("data-val-user-from") === message.UserFromId.toString() && !newMsg){
                var dateLast= $(".chat-text-wrapper p:last", this.$messagesWrapper).attr('title');
                if(typeof dateLast!=='undefined'){                    
                    var dtimeLast=new Date(dateLast),dtimeSend=new Date(sended_date);
                    if(sended_date.substring(0,14)===dateLast.substring(0,14) && Math.floor((dtimeSend-dtimeLast)/1000/60)>10 )
                        newMsg=true;
                }else newMsg=true;
            }
            /* FIN CONTROL FECHAS */
            if ($lastMessage.length && $lastMessage.attr("data-val-user-from") === message.UserFromId.toString() && !newMsg ) { // agregado && !newMsg
                // we can just append text then
                $(".chat-text-wrapper", $lastMessage).find('p.sended').removeClass('sended');
                $messageP.appendTo($(".chat-text-wrapper", $lastMessage));
            } else {
                // in this case we need to create a whole new message
                var $chatMessage = $("<div/>").addClass("chat-message").attr("data-val-user-from", message.UserFromId);
                $chatMessage.appendTo(this.$messagesWrapper);
                var extra=($lastMessage.attr("data-val-user-from")===message.UserFromId.toString()&&lastDay===sended_date.substring(0,10)?'no-new':'');
                var $gravatarWrapper = $("<div/>").addClass("chat-gravatar-wrapper "+messageClass+" "+extra).appendTo($chatMessage);
                var $textWrapper = $("<div/>").addClass("chat-text-wrapper "+messageClass+" "+extra).appendTo($chatMessage);
                
                // add text
                $messageP.appendTo($textWrapper);

                // add image
                var $img = $("<img/>").addClass("profile-picture").appendTo($gravatarWrapper);
                this.options.adapter.server.getUserInfo(message.UserFromId, function (user) {
                    $img.attr("src", decodeURI(user.ProfilePictureUrl));
                });
            }
        }

        if (scroll)
            this.adjustScroll();
    };
    return MessageBoard;
})();

$.fn.messageBoard = function (options) {
    if (this.length) {
        this.each(function () {
            var data = new MessageBoard($(this), options);
            $(this).data('messageBoard', data);
        });
    }
    return this;
};

Number.prototype.padLeft = function(base,chr){
   var  len = (String(base || 10).length - String(this).length)+1;
   return len > 0? new Array(len).join(chr || '0')+this : this;
};