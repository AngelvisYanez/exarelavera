var ChatMessageInfo = (function () {
    function ChatMessageInfo() {
    }
    return ChatMessageInfo;
})();

var UserStatusType;
(function (UserStatusType) {
    UserStatusType[UserStatusType["Offline"] = 0] = "Offline";
    UserStatusType[UserStatusType["Online"] = 1] = "Online";
})(UserStatusType || (UserStatusType = {}));

/// <summary>
/// Information about a chat user
/// </summary>
var ChatUserInfo = (function (data) {
    /// User chat status. For now, it only supports online and offline
    function ChatUserInfo(data) {
		if(typeof data==='object'){
			this.Id = data['Id'];
			this.RoomId = data['RoomId'];
			this.Name = data['Name'];
			this.Email = data['Email'];
			this.ProfilePictureUrl = data['ProfilePictureUrl'];
			this.Status = data['Status'];
			//console.log(data);
		}
    }
    return ChatUserInfo;
})();

var ChatRoomInfo = (function () {
    function ChatRoomInfo() {
    }
    return ChatRoomInfo;
})();

var ChatTypingSignalInfo = (function () {
    function ChatTypingSignalInfo(UserFrom) {
        if(typeof UserFrom==='object')
            this.UserFrom=UserFrom;
    }
    return ChatTypingSignalInfo;
})();

var ChatUserListChangedInfo = (function () {
    function ChatUserListChangedInfo() {
    }
    return ChatUserListChangedInfo;
})();

var ChatRoomListChangedInfo = (function () {
    function ChatRoomListChangedInfo() {
    }
    return ChatRoomListChangedInfo;
})();