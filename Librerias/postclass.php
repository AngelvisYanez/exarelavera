<?php
class Post_Block {
    function startPost() {
        echo "<input type='hidden' name='postID' ";
        echo "value='".md5(uniqid(rand(), true))."'>";
    }
    
    function postBlock($postID) {
        if(session_id()==='')  session_start();        
        if(isset($Ses_Prs_Cod)&&$Ses_Prs_Cod==1){	 echo $postID."=".$_SESSION['postID_1'];    }
        if(isset($_SESSION['postID_1'])) {			
            if ($postID == $_SESSION['postID_1']) {
                return false;
            } else {
                $_SESSION['postID_1'] = $postID;
                return true;
            }
        } else {			
            $_SESSION['postID_1'] = $postID;
            return true;
        }
	
    }
}
?>