<?php

require_once('ProcessConversation.php');

$string = "I'm sorry, I'm extremely busy right now. I just looked at the clock, and it's 12:54 AM, I've still got a lot of work to do.  Don't worry about the event tomorrow, it's been moved ahead a week, the 28th of december. Remember though, you've got to call to get a ticket soon, their # is 212-323-1239 and they live at Los Angeles. Their website says it costs $23 per person. 
If you've got enough time, they have some more information on their website, http://theevent.com.  
Regards,
David (david32@gmail.com)";

$conversation = new ProcessConversation\ProcessConversation($string);

echo $string . '<br>';
var_dump($conversation->extract());