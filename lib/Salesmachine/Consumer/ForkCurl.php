<?php

class Salesmachine_Consumer_ForkCurl extends Salesmachine_QueueConsumer {

  protected $type = "ForkCurl";
  protected $endpoint;


  /**
   * Creates a new queued fork consumer which queues fork and identify
   * calls before adding them to
   * @param string $secret
   * @param array  $options
   *     boolean  "debug" - whether to use debug output, wait for response.
   *     number   "max_queue_size" - the max size of messages to enqueue
   *     number   "batch_size" - how many messages to send in a single request
   */
  public function __construct($token, $secret, $endpoint, $options = array()) {
    $this->endpoint = $endpoint;
    parent::__construct($token, $secret, $options);
  }

  /**
   * Make an async request to our API. Fork a curl process, immediately send
   * to the API. If debug is enabled, we wait for the response.
   * @param  array   $messages array of all the messages to send
   * @return boolean whether the request succeeded
   */
  public function flushBatch($messages) {

    $body = $this->payload($messages);
    $payload = json_encode($body);

    # Escape for shell usage.
    $payload = escapeshellarg($payload);

    $protocol = $this->ssl() ? "https://" : "http://";
    $id = $this->token . ":" . $this->secret . "@";
    $host = $this->host();
    $path = "/v1/" . $this->endpoint;
    $url = $protocol . $id . $host . $path;

    $cmd = "curl -X POST -H 'Content-Type: application/json'";
    $cmd.= " -d " . $payload . " '" . $url . "'";

    if (!$this->debug()) {
      $cmd .= " > /dev/null 2>&1 &";
    }

    exec($cmd, $output, $exit);

    if ($exit != 0) {
      $this->handleError($exit, $output);
    }

    return $exit == 0 && (!isset($output[0]) || !isset(json_decode($output[0], true)['error']));
  }
}
