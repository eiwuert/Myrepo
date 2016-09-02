<?php
/*
 *       Logging class: used to generate log files
 *       Randy Klepetko 07-12-2012
 *
 */
    class Logging{
        // define default log file
        private $log_file = '/tmp/preauthlogfile.log';
        // define file pointer
        private $fp = null;
        // set log file (path and name)
        public function lfile($path) {
            $this->log_file = $path;
        }
        // write message to the log file
        public function lwrite($message){
            // if file pointer doesn't exist, then open log file
            if (!$this->fp) $this->lopen();
            // define script name
            $script_name = pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME);
            // define current time
            $time = date('m-d-y H:i:s');
            // write current time, script name and message to the log file
            fwrite($this->fp, "$time ($script_name) $message\n");
        }
        // recursive function to write an array as XML out to the log file
        public function lwriteArray($array, $level=0, $string=''){
            foreach(array_keys($array) as $key){
                //$space = '';
                // use the below line to make the xml string human readable
                $space = chr(13).str_pad('',$level,'  ');
                $string .= $space.'<'.$key;
                $close_tag = false;
                $child = $array[$key];
                if ($child == '') {
                    // not set a value, close tag in tag opening
                } else {
                    if (gettype($child) == "array"){ // the child is an array
                        if (count($child) > 0) {     // not an empty array
                            $string .= '>';
                            // here is the magic, recur the routine for the children
                            $string = XMLgen($level+1, $child, $string) . $space;
                            $close_tag = true;
                        }
                    } else {  // the child is just a value
                        $string .= '>'. $child;
                        $close_tag = true;
                    }
                }
                if (!$close_tag){
                    $string .= '/>';
                } else {
                    $string .= '</'.$key.'>';
                }
            }
            return $string;
        }
        // open log file
        private function lopen(){
            // define log file path and name
            $lfile = $this->log_file;
            // define the current date (it will be appended to the log file name)
            $today = date('Y-m-d');
            // open log file for writing only; place the file pointer at the end of the file
            // if the file does not exist, attempt to create it
            $this->fp = fopen($lfile, 'a') or exit("Can't open $lfile!");
        }
    }