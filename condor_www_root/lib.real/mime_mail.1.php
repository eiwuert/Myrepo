<?

class mime_mail
{
   var $parts;
   var $to;
   var $from;
   var $headers;
   var $subject;
   var $body;

   /*
    *     void mime_mail()
    *     class constructor
    */

   function mime_mail()
   {
      $this->parts		= array();
      $this->to			= "";
      $this->from		= "";
      $this->subject	= "";
      $this->body		= "";
      $this->headers	= "";
   }

   /*
    *     void add_attachment(string message, [string name], [string ctype])
    *     Add an attachment to the mail object
    */

   function add_attachment($message, $name =  "", $ctype = "application/octet-stream", $disposition = "", $encode="base64")
   {
      $this->parts[] = array (
           						"ctype"			=> $ctype,
          						"message"		=> $message,
           						"encode"		=> $encode,
           						"name"			=> $name,
								"disposition"	=> $disposition
                             );
   }

   /*
    *      void build_message(array part=
    *      Build message parts of an multipart mail
    */

   function build_message($part) {
      $message = $part["message"];
      if ($part["encode"] == "base64")
      {
	 $message = chunk_split(base64_encode($message));
	 $encoding =  "base64";
      }
      else
      {
         $encoding =  "7bit";
      }
      $return_str =  "Content-Type: " . $part["ctype"];
      if(!empty($part["name"])) {
         $return_str .= "; name=\"" . $part["name"] . "\"";
      }
      $return_str .= "\nContent-Transfer-Encoding: $encoding\n";
      if(!empty($part["disposition"])) {
         $return_str .= "Content-Disposition: " . $part["disposition"];
	      if(!empty($part["name"])) {
		 $return_str .= "; filename=\"" . $part["name"] . "\"";
	      }
         $return_str .= "\n";
      }
      $return_str .= "\n$message\n";

      return $return_str;
   }

   /*
    *      void build_multipart()
    *      Build a multipart mail
    */

   function build_multipart() {
      $boundary =  "b".md5(uniqid(time()));
      $multipart =
         "Content-Type: multipart/mixed; boundary = $boundary\n\nThis is a MIME encoded message.\n\n--$boundary";

         for($i = sizeof($this->parts)-1; $i >= 0; $i--)
      {
         $multipart .=  "\n".$this->build_message($this->parts[$i]).
            "--$boundary";
      }
      return $multipart.=  "--\n";
   }

   /*
    *      string get_mail()
    *      returns the constructed mail
    */

   function get_mail($complete = true) {
      $mime =  "";
      if (!empty($this->from))
         $mime .=  "From: ".$this->from. "\n";
      if (!empty($this->headers))
         $mime .= $this->headers. "\n";

      if ($complete) {
         if (!empty($this->to)) {
            $mime .= "To: $this->to\n";
         }
         if (!empty($this->subject)) {
            $mime .= "Subject: $this->subject\n";
         }
      }

      if (!empty($this->body))
         $this->add_attachment($this->body,  "", "text/plain; charset=\"US-ASCII\"", "", "none");
      $mime .=  "MIME-Version: 1.0\n".$this->build_multipart();

      return $mime;
   }

   /*
    *      bool send()
    *      Send the mail (last class-function to be called);
	   returns the mail() result: TRUE if successfully accepted for delivery.
    */

   function send() {
      $mime = $this->get_mail(false);
      $result = mail($this->to, $this->subject,  "", $mime);

      return $result;
   }
};  // end of class

?>
