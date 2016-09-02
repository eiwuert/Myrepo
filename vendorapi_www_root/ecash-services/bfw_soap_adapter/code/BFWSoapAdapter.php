<?php

require 'prpc/server.php';

class BFWSoapAdapter extends Prpc_Server
{
    private $soap;
    private $debug_enabled = false;
    private $logger;

    public function setSoapClient(SoapClient $c)
    {
        $this->soap = $c;
    }

    public function setDebug($debug_enabled)
    {
        $this->debug_enabled = $debug_enabled;
    }

    public function setLogger(Log_ILog_1 $logger)
    {
        $this->logger = $logger;
    }

    private function log($message, Exception $exception = NULL)
    {
        if (isset($this->logger))
        {
            $error_code = 'ERRCODE' . substr(strtoupper(md5('ERROR ' . time() . rand(0, getrandmax()))), 0, 8);
            $log = "[SessionService][$error_code]";
            if (isset($exception) && !is_null($exception))
            {
                $log .= " ".get_class($exception) . ' -- ' . $exception->getMessage();
            }
            if (trim($message) != "")
            {
                $log .= ", ".$message;
            }
            $this->logger->write($log);
        }
    }

    public function Process_Data($license, $site_type, $session_id, $data, $debug)
    {
//error_log('');
//error_log('Sending request: '.$session_id);
//error_log(serialize($license));
//error_log(serialize($site_type));
//error_log(serialize($cur_page));
//error_log(serialize($data));
            $cur_page = isset($data['page'] ) ? $data['page'] : '';
            unset($data['page']);

            $server = $data['client_state']['_SERVER'];
            unset($data['client_state']);

            $request = new stdClass();
            $request->field_data = $this->packData($data);
            $request->_SERVER = $server;

            $soap_exceptions = null;

            try {
                    $response = $this->soap->nextPage($license, $session_id, $site_type, $cur_page, $request);
//error_log('');
//error_log('Response from : '.$session_id);
//error_log(serialize($response));
            } catch (Exception $e) {
                    $soap_exceptions = $e;
                    $root = isset($data['client_url_root']) ? $data['client_url_root'] : '';

                    if (!$debug) {
                            error_log("Received exception while processing request");
                    } else {
                            error_log("Received exception while processing request");
                    }
                    error_log(print_r($e,true));

                    $response = new stdClass();
                    $response->nextPage = 'try_again';
                    $response->tokens = array();
                    $response->client_state = (object)array(
                            'config' => array(),
                            '_SERVER' => array(),
                            'data' => array(),
                    );
            }

            $state = $this->unpackClientState($response->site_state);
            $tokens = $this->unpackData((array)$response->data);

            // XXX set these in the backend
            $tokens['no_popups'] = TRUE;
            $state['prevent_p1_popunder'] = TRUE;

            $eds_response = (object)array(
                    'page' => $response->nextPage,
                    'errors' => (array)$response->errors,
                    'client_state' => $state,
                    'unique_id' => $session_id,
                    'exit_strategy' => $this->unpackExitStrategy($response->site_state->exit_strategy_config),
                    'session' => (array)$response,
            		'user_messages' => (array)$response->user_messages,
            );

            // unfix eds_page craziness
            if ($response->nextPage === 'eds_page')
            {
                    $eds_response->page = $cur_page;
                    $eds_response->eds_page = array(
                            'content' => $tokens['content'],
                            'type' => $tokens['type'],
                            'action' => $tokens['action'],
                    );
                    if (trim($tokens['nextPage']) != "")
                    {
                            $response->nextPage = $tokens['nextPage'];
                    }
                    unset($tokens['content']);
            }

            $eds_response->data = $tokens;
            if ($debug)
            {
				// TODO: Re-enable when front end templates 
				// don't access things out of the session
				// $eds_response->session = $response;
            }

            if ($this->debug_enabled)
            {
                if ($response->nextPage == 'try_again' || $eds_response->page == "try_again")
                {
                    $data  =  $cur_page;
                    $data .= " =:=:= ".print_r($request, 1);
                    $data .= " =:=:= ".print_r($response, 1);
                    $data .= " =:=:= ".print_r($eds_response, 1);

                    $this->log($data, $soap_exceptions);
                }
            }

            return $eds_response;
    }

    private function packClientState(array $state)
    {
            $client_state = new stdClass();
            $client_state->_SERVER = $state['_SERVER'];
            $client_state->config = $state['config'];

            // assign the remaining stuff to data
            unset($state['_SERVER'], $state['config']);
            $client_state->data = $state;

            return $client_state;
    }

    private function unpackClientState($state)
    {
            $client_state = (array)$state->data;
            $client_state['config'] = (array)$state->config;
            return $client_state;
    }

    private function unpackExitStrategy($exit_strategy)
    {
            return array(
                    'unique_page_stat' => (object)array_flip((array)$exit_strategy->unique_page_stat->stat),
                    'unique_stat' => (object)array_flip((array)$exit_strategy->unique_stat->stat),
                    'data' => (array)$exit_strategy->exit_strategies,
            );
    }

    private function packData(array $data)
    {
            $new = array();
            $stack = $data;

            do {
                    $copy = $stack;
                    $stack = array();

                    foreach ($copy as $key=>$value)
                    {
                            if (is_array($value))
                            {
                                    foreach ($value as $k=>$v)
                                    {
                                            $k = $key.'__'.$k;
                                            if (is_array($v))
                                            {
                                                    $stack[$k] = $v;
                                            }
                                            else
                                            {
                                                    $new[$k] = $v;
                                            }
                                    }
                            }
                            else
                            {
                                    $new[$key] = $value;
                            }
                    }
            } while (!empty($stack));

            return $new;
    }

    private function unpackData(array $data)
    {
            $new = array();

            foreach ($data as $key=>$value)
            {
                    $path = explode('__', $key);
                    if (count($path) > 1)
                    {
                            $node = &$new;
                            $key = array_pop($path);
                            foreach ($path as $k)
                            {
                                    if (!isset($node[$k])
                                            || !is_array($node[$k]))
                                    {
                                            $node[$k] =  array();
                                    }
                                    $node = &$node[$k];
                            }
                            $node[$key] = $value;
                    }
                    else
                    {
                            $new[$key] = $value;
                    }
            }

            return $new;
    }
}
?>
