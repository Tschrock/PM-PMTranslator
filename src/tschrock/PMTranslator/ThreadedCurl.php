<?php

namespace tschrock\PMTranslator;

class ThreadedCurl {

    public $outstanding_requests;
    public $multi_handle;

    public function __construct() {

        $this->outstanding_requests = array();
        $this->multi_handle = curl_multi_init();
    }

    //Ensure all the requests finish nicely
    public function __destruct() {
        $this->clearAllRequests();
    }

    // Sets how many requests can be outstanding at once before we block and wait for one to
    // finish before starting the next one
    public function setMaxRequests($in_max_requests) {
        $this->max_requests = $in_max_requests;
    }

    // Sets the options to pass to curl, using the format of curl_setopt_array()
    public function setOptions($in_options) {

        $this->options = $in_options;
    }

    // Start a fetch from the $url address, calling the $callback function passing the optional
    // $user_data value. The callback should accept 3 arguments, the url, curl handle and user
    // data, eg on_request_done($url, $ch, $user_data);
    public function startRequest($url, $callback, $optArray = array(), $user_data = array(), $post_fields = null) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt_array($ch, $optArray);
        curl_setopt($ch, CURLOPT_URL, $url);

        if (isset($post_fields)) {
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        }

        curl_multi_add_handle($this->multi_handle, $ch);

        $ch_array_key = (int) $ch;

        $this->outstanding_requests[$ch_array_key] = array(
            'url' => $url,
            'callback' => $callback,
            'user_data' => $user_data,
        );

        #$this->checkForCompletedRequests();
    }

    public function clearAllRequests() {
        unset($this->outstanding_requests);
    }

    // Checks to see if any of the outstanding requests have finished
    public function checkForCompletedRequests() {
        /*
          // Call select to see if anything is waiting for us
          if (curl_multi_select($this->multi_handle, 0.0) === -1)
          return;

          // Since something's waiting, give curl a chance to process it
          do {
          $mrc = curl_multi_exec($this->multi_handle, $active);
          } while ($mrc == CURLM_CALL_MULTI_PERFORM);
         */
        // fix for https://bugs.php.net/bug.php?id=63411
        do {
            $mrc = curl_multi_exec($this->multi_handle, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($this->multi_handle) != -1) {
                do {
                    $mrc = curl_multi_exec($this->multi_handle, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            } else
                return;
        }

        // Now grab the information about the completed requests
        while ($info = curl_multi_info_read($this->multi_handle)) {

            $ch = $info['handle'];
            $ch_array_key = (int) $ch;

            if (!isset($this->outstanding_requests[$ch_array_key])) {
                die("Error - handle wasn't found in requests: '$ch' in " .
                        print_r($this->outstanding_requests, true));
            }

            $request = $this->outstanding_requests[$ch_array_key];

            $url = $request['url'];
            $content = curl_multi_getcontent($ch);
            $callback = $request['callback'];
            $user_data = $request['user_data'];

            call_user_func($callback, $content, $url, $ch, $user_data);

            unset($this->outstanding_requests[$ch_array_key]);

            curl_multi_remove_handle($this->multi_handle, $ch);
        }
    }

}
