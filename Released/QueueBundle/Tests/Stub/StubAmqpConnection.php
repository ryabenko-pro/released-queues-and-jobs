<?php

namespace Released\QueueBundle\Tests\Stub;

use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Wire\AMQPReader;

class StubAmqpConnection extends AbstractConnection
{

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct()
    {
        // Do not call parent
    }

    /** {@inheritDoc} */
    public static function getProtocolVersion() { }

    /** {@inheritDoc} */
    public function getChannelId() { }

    /** {@inheritDoc} */
    public function setBodySizeLimit($max_bytes) { }

    /** {@inheritDoc} */
    public function getConnection() { }

    /** {@inheritDoc} */
    public function getMethodQueue() { }

    /** {@inheritDoc} */
    public function hasPendingMethods() { }

    /** {@inheritDoc} */
    public function dispatch($method_sig, $args, $amqpMessage) { }

    /** {@inheritDoc} */
    public function next_frame($timeout = 0) { }

    /** {@inheritDoc} */
    protected function send_method_frame($method_sig, $args = '') { }

    /** {@inheritDoc} */
    protected function prepare_method_frame($method_sig, $args = '', $pkt = null) { }

    /** {@inheritDoc} */
    public function wait_content() { }

    /** {@inheritDoc} */
    protected function createMessage($propertyReader, $contentReader) { }

    /** {@inheritDoc} */
    public function wait($allowed_methods = null, $non_blocking = false, $timeout = 0) { }

    /** {@inheritDoc} */
    protected function process_deferred_methods($allowed_methods) { }

    /** {@inheritDoc} */
    protected function dispatch_deferred_method($queued_method) { }

    /** {@inheritDoc} */
    protected function validate_method_frame($frame_type) { }

    /** {@inheritDoc} */
    protected function validate_header_frame($frame_type) { }

    /** {@inheritDoc} */
    protected function validate_body_frame($frame_type) { }

    /** {@inheritDoc} */
    protected function validate_frame($frameType, $expectedType, $expectedMessage) { }

    /** {@inheritDoc} */
    protected function validate_frame_payload($payload) { }

    /** {@inheritDoc} */
    protected function build_method_signature($payload) { }

    /** {@inheritDoc} */
    protected function extract_args($payload) { }

    /** {@inheritDoc} */
    protected function should_dispatch_method($allowed_methods, $method_sig) { }

    /** {@inheritDoc} */
    protected function maybe_wait_for_content($method_sig) { }

    /** {@inheritDoc} */
    protected function dispatch_to_handler($handler, array $arguments) { }

    /** {@inheritDoc} */
    protected function connect() { }

    /** {@inheritDoc} */
    public function reconnect() { }

    /** {@inheritDoc} */
    protected function safeClose() { }

    /** {@inheritDoc} */
    public function select($sec, $usec = 0) { }

    /** {@inheritDoc} */
    public function set_close_on_destruct($close = true) { }

    /** {@inheritDoc} */
    protected function close_input() { }

    /** {@inheritDoc} */
    protected function close_socket() { }

    /** {@inheritDoc} */
    public function write($data) { }

    protected function do_close() { }

    /** {@inheritDoc} */
    public function get_free_channel_id() { }

    /** {@inheritDoc} */
    public function send_content($channel, $class_id, $weight, $body_size, $packed_properties, $body, $pkt = null) { }

    /** {@inheritDoc} */
    public function prepare_content($channel, $class_id, $weight, $body_size, $packed_properties, $body, $pkt = null) { }

    /** {@inheritDoc} */
    protected function send_channel_method_frame($channel, $method_sig, $args = '', $pkt = null) { }

    /** {@inheritDoc} */
    protected function prepare_channel_method_frame($channel, $method_sig, $args = '', $pkt = null) { }

    /** {@inheritDoc} */
    protected function wait_frame($timeout = 0) { }

    /** {@inheritDoc} */
    protected function wait_channel($channel_id, $timeout = 0) { }

    /** {@inheritDoc} */
    public function channel($channel_id = null) { }

    /** {@inheritDoc} */
    public function close($reply_code = 0, $reply_text = '', $method_sig = array(0, 0)) { }

    /** {@inheritDoc} */
    protected function connection_close(AMQPReader $reader) { }

    /** {@inheritDoc} */
    protected function x_close_ok() { }

    /** {@inheritDoc} */
    protected function connection_close_ok($args) { }

    /** {@inheritDoc} */
    protected function x_open($virtual_host, $capabilities = '', $insist = false) { }

    /** {@inheritDoc} */
    protected function connection_open_ok($args) { }

    /** {@inheritDoc} */
    protected function connection_redirect($args) { }

    /** {@inheritDoc} */
    protected function connection_secure($args) { }

    /** {@inheritDoc} */
    protected function x_secure_ok($response) { }

    /** {@inheritDoc} */
    protected function connection_start($args) { }

    /** {@inheritDoc} */
    protected function x_start_ok($clientProperties, $mechanism, $response, $locale) { }

    /** {@inheritDoc} */
    protected function connection_tune($args) { }

    /** {@inheritDoc} */
    protected function x_tune_ok($channel_max, $frame_max, $heartbeat) { }

    /** {@inheritDoc} */
    public function getSocket() { }

    /** {@inheritDoc} */
    protected function getIO() { }

    /** {@inheritDoc} */
    protected function connection_blocked(AMQPReader $args) { }

    /** {@inheritDoc} */
    protected function connection_unblocked(AMQPReader $args) { }

    /** {@inheritDoc} */
    public function set_connection_block_handler($callback) { }

    /** {@inheritDoc} */
    public function set_connection_unblock_handler($callback) { }

    /** {@inheritDoc} */
    public function isConnected() { }

    /** {@inheritDoc} */
    protected function setIsConnected($is_connected) { }

    /** {@inheritDoc} */
    protected function closeChannels() { }

    /** {@inheritDoc} */
    public function connectOnConstruct() { }

    /** {@inheritDoc} */
    public function getServerProperties() { }
}