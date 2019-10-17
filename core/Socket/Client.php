<?php
	namespace Novae\Socket;

	class Client extends Socket {
		protected $proto = null; // The detected protocol, if known.  ToDo:   Make read-only when parser is available.

		public function connect($host)
		{
			if ($this->socket)
			{
				$this->disconnect();
			}

			$context = stream_context_create();
			if (($x = strpos($host, "://")) !== FALSE)
			{
				$this->proto = strtolower(substr($host, 0, $x));
			}

			if ($this->proto == "ssl")
			{
				stream_context_set_option($context, "ssl", "verify_host", TRUE);
			}

			$errno = $errstr = FALSE;

			if (!($fp = stream_socket_client($host, $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $context)))
			{
				\Novae\Log::error("Connection failed", [ "errno" => $errno, "errstr" => $errstr, "context" => $context]);
				throw new \Exception("Connection failed"); // ToDo : get error message
			}

			if (!stream_set_blocking($fp, FALSE))
			{
				\Novae\Log::error("Connection blocking", "Failed to disable blocking on socket destined to ${host}");
				throw new \Exception("Connection failed (blocking)");
			}
			$this->socket = $fp; /* setter for $this->socket includes a reset of the buffers */
		}

	}
