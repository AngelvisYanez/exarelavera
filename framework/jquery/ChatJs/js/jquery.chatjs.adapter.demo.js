/*
 * @autor       Erik Niebla A.
 * @copyright   (c)2015, EN Systems Apps
 */
var chatServerUrl='';
var debugChat=false;
var DemoAdapterConstants = (function () {
    function DemoAdapterConstants() {
    }
    DemoAdapterConstants.CURRENT_USER = new ChatUserInfo();

    DemoAdapterConstants.ECHOBOT = true;
    DemoAdapterConstants.ECHOBOT_USER_ID = 'bot';

    DemoAdapterConstants.DEFAULT_ROOM_ID = 'default';
    DemoAdapterConstants.DEFAULT_ROOM_NAME = 'Default';

    DemoAdapterConstants.DEFAULT_ROOM_USERS_ONLINE = new Array();

    return DemoAdapterConstants;
})();
var userListChangedInfo= new ChatUserListChangedInfo();

var DemoClientAdapter = (function () {
    function DemoClientAdapter() {
        this.messagesChangedHandlers = [];
        this.typingSignalReceivedHandlers = [];
        this.userListChangedHandlers = [];
        this.handlersIndex = new Array();
    }
    // adds a handler to the messagesChanged event
    DemoClientAdapter.prototype.onMessagesChanged = function (handler,otherUserId) {                
        var shouldAddHandler=true;
        if ('undefined'!==typeof otherUserId)
            for(var i=0;i<this.handlersIndex.length;i++)
                if(this.handlersIndex[i]['id']===otherUserId){                    
                    this.messagesChangedHandlers[i]=handler;
                    shouldAddHandler=false;break;
                }        
        if(shouldAddHandler){           
            this.handlersIndex.push({id:otherUserId});            
            this.messagesChangedHandlers.push(handler);
            if(debugChat)
                console.log("Adding Handlers PM. Index => " + (this.handlersIndex.length-1) +' for otherUserId=> ' + otherUserId);
        }
    };

    // adds a handler to the typingSignalReceived event
    DemoClientAdapter.prototype.onTypingSignalReceived = function (handler) {
        this.typingSignalReceivedHandlers.push(handler);
    };

    // adds a handler to the userListChanged event
    DemoClientAdapter.prototype.onUserListChanged = function (handler) {
        this.userListChangedHandlers.push(handler);
    };

    DemoClientAdapter.prototype.triggerMessagesChanged = function (message) {
        for (var i = 0; i < this.messagesChangedHandlers.length; i++)
            this.messagesChangedHandlers[i](message);
    };

    DemoClientAdapter.prototype.triggerTypingSignalReceived = function (typingSignal) {
        for (var i = 0; i < this.typingSignalReceivedHandlers.length; i++)
            this.typingSignalReceivedHandlers[i](typingSignal);
    };

    DemoClientAdapter.prototype.triggerUserListChanged = function (userListChangedInfo) {
        for (var i = 0; i < this.userListChangedHandlers.length; i++)
            this.userListChangedHandlers[i](userListChangedInfo);
    };
    
    return DemoClientAdapter;
})();

var DemoServerAdapter = (function () {
    function DemoServerAdapter(clientAdapter) {
        //alert(chatServerUrl);
        this.clientAdapter = clientAdapter;

        this.setUsers(DemoAdapterConstants.CURRENT_USERS_ONLINE);
        setTimeout(function(){adapter.server.heartBeat();}, 5000);

        // configuring client to return every event to me
//        this.clientAdapter.onMessagesChanged(function (message) {
//            return function () {
//                
//            };
//        });
    }
    DemoServerAdapter.prototype.heartBeat = function (){
        var _this=this;
        $.post(chatServerUrl,{heartBeatChat:true}, function( response ) {
            if(response['success']){                
                _this.setUsers(response['users'],true);
                response['signals'].forEach(function(sig) {
                    _this.getUserInfo(sig['UserFromId']*1, function (otherUserInfo) {
                        var typingSignal = new ChatTypingSignalInfo(otherUserInfo);
                        typingSignal.UserToId = DemoAdapterConstants.CURRENT_USER.Id;                            
                        _this.clientAdapter.triggerTypingSignalReceived(typingSignal);
                    });
                });
                response['messages'].forEach(function(msg) {
                    _this.bounceMsg(msg);
                });
                $('#uOnline').html(response['room']['UsersOnline']);               
            }
        },'json').always(function() {setTimeout(function(){adapter.server.heartBeat();}, 5000);});
    };
    DemoServerAdapter.prototype.bounceMsg = function (Message,done){
        this.clientAdapter.triggerMessagesChanged(Message);
        if(typeof done!== 'undefined')
            done([]);
    };   
    DemoServerAdapter.prototype.setUsers = function (users,reload){        
        reload=(typeof reload==='undefined'?false:reload);

        var userAux=new Array();
        this.rooms = new Array();

//        // configuring users
//        var echoBotUser = new ChatUserInfo();
//        echoBotUser.Id = DemoAdapterConstants.ECHOBOT_USER_ID;
//        echoBotUser.RoomId = DemoAdapterConstants.DEFAULT_ROOM_ID;
//        echoBotUser.Name = "Echobot";
//        echoBotUser.Email = "echobot1984@gmail.com";
//        echoBotUser.ProfilePictureUrl = "http://www.gravatar.com/avatar/4ec6b20c5fed48b6b01e88161c0a3e20.jpg";
//        echoBotUser.Status = 1 /* Online */;

        DemoAdapterConstants.CURRENT_USER.RoomId = DemoAdapterConstants.DEFAULT_ROOM_ID;
        DemoAdapterConstants.CURRENT_USER.Email = "";
        DemoAdapterConstants.CURRENT_USER.ProfilePictureUrl = "../../framework/jquery/ChatJs/images/userBlue.png";
        DemoAdapterConstants.CURRENT_USER.Status = 1 /* Online */;

        userAux.push(DemoAdapterConstants.CURRENT_USER);
//        userAux.push(echoBotUser);

        if(typeof users!=='undefined'&&null!==users){
            users.forEach(function(entry) {
                userAux.push(new ChatUserInfo(entry));
            });
        }        
        this.users=userAux;

        // configuring rooms
        var defaultRoom = new ChatRoomInfo();
        defaultRoom.Id = DemoAdapterConstants.DEFAULT_ROOM_ID;
        defaultRoom.Name = DemoAdapterConstants.DEFAULT_ROOM_NAME;
        defaultRoom.UsersOnline = this.users.length;
        this.rooms.push(defaultRoom);

        if(reload){
            userListChangedInfo = new ChatUserListChangedInfo();
            userListChangedInfo.RoomId = DemoAdapterConstants.DEFAULT_ROOM_ID;
            userListChangedInfo.UserList=new Array();
            userListChangedInfo.UserList=this.users;
            adapter.server.clientAdapter.triggerUserListChanged(userListChangedInfo);
        }

    };
    DemoServerAdapter.prototype.sendMessage = function (roomId, conversationId, otherUserId, messageText, clientGuid, done) {
        var _this = this;
        if(debugChat)
            console.log("DemoServerAdapter: sendMessage");

        // we have to send the current message to the current user first
        // in chatjs, when you send a message to someone, the same message bounces back to the user
        // just so that all browser windows are synchronized
        var bounceMessage = new ChatMessageInfo();
        bounceMessage.UserFromId = DemoAdapterConstants.CURRENT_USER.Id; // It will from our user
        bounceMessage.UserToId = otherUserId; // ... to the Echobot
        bounceMessage.RoomId = roomId;
        bounceMessage.ConversationId = conversationId;
        bounceMessage.Message = messageText;
        bounceMessage.ClientGuid = clientGuid;

        $.post(chatServerUrl,bounceMessage, function( response ) {
            if(response['success'])
                _this.bounceMsg(bounceMessage,done);
        },'json').fail(function(error) {});
    };

    DemoServerAdapter.prototype.sendTypingSignal = function (roomId, conversationId, userToId, done) {
        if(debugChat)
            console.log("DemoServerAdapter: sendTypingSignal");
        $.post(chatServerUrl,{signalChat:true,UserFromId:DemoAdapterConstants.CURRENT_USER.Id,UserToId:userToId}, function( response ) {
            if(response['success']){done([]);}
        },'json').fail(function(error) {});
    };

    DemoServerAdapter.prototype.getMessageHistory = function (roomId, conversationId, otherUserId, done) {
        if(debugChat)
        console.log("DemoServerAdapter: getMessageHistory");
        $.post(chatServerUrl,{historyChat:true,UserFromId:DemoAdapterConstants.CURRENT_USER.Id,UserToId:otherUserId}, function( response ) {
            if(response['success']){
                done(response['history']);
            }
        },'json').fail(function(error) {});
    };

    DemoServerAdapter.prototype.getUserInfo = function (userId, done) {
        if(debugChat)
            console.log("DemoServerAdapter: getUserInfo "+userId);
        var user = null;
        for (var i = 0; i < this.users.length; i++) {            
            if (''+this.users[i].Id === ''+userId) {
                
                user = this.users[i];
                break;
            }
        }
        if (user === null){
            if(debugChat)
                console.log("User doesn't exit. User id: " + userId);
        }else
            done(user);
    };

    DemoServerAdapter.prototype.getUserList = function (roomId, conversationId, done) {
        if(debugChat)
            console.log("DemoServerAdapter: getUserList");

        if (roomId === DemoAdapterConstants.DEFAULT_ROOM_ID) {
            done(this.users);
            return;
        }

        throw "The given room or conversation is not supported by the demo adapter";
    };

    DemoServerAdapter.prototype.enterRoom = function (roomId, done) {
        if(debugChat)
            console.log("DemoServerAdapter: enterRoom");

        if (roomId !== DemoAdapterConstants.DEFAULT_ROOM_ID)
            throw "Only the default room is supported in the demo adapter";

        userListChangedInfo.RoomId = DemoAdapterConstants.DEFAULT_ROOM_ID;
        userListChangedInfo.UserList = this.users;

        this.clientAdapter.triggerUserListChanged(userListChangedInfo);
    };

    DemoServerAdapter.prototype.leaveRoom = function (roomId, done) {
        if(debugChat)
            console.log("DemoServerAdapter: leaveRoom");
    };

    // gets the given user from the user list
    DemoServerAdapter.prototype.getUserById = function (userId) {
        for (var i = 0; i < this.users.length; i++) {
            if (this.users[i].Id === userId)
                return this.users[i];
        }
        throw "Could not find the given user";
    };
    return DemoServerAdapter;
})();

var DemoAdapter = (function (url) {
    function DemoAdapter(url) {
        chatServerUrl=url;
    }
    // called when the adapter is initialized
    DemoAdapter.prototype.init = function (done) {
        this.client = new DemoClientAdapter();
        this.server = new DemoServerAdapter(this.client);
        done();
    };
    return DemoAdapter;
})();