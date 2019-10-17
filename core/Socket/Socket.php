<?php
	namespace Novae\Socket;

	/** Read/Write buffers as a base for Client/Server classes
	*/

	class Socket extends \Novae\CoreObject {
		use \Novae\Event\ProviderTrait;

		protected $readBuffer = "";
		protected $readBufferSize = 0;
		protected $writeBuffer = "";
		protected $writeBufferSize = 0;

		protected $_socket = null;

		function writeToBuffer( $message )
		{
			$this->writeBuffer .= $message;
			$this->writeBufferSize += strlen($message);
		}

		function __setSocket( $socket )
		{
			if (!is_null($this->_socket))
			{
				if (!is_null($socket))
					\Log::warn("Socket-disconnect-implicit", "Implicitely closing Socket pointer due to new socket provided", ["old socket" => $socket, "new socket" => $socket]);

				$this->disconnect(FALSE); // disconnect with-out a timed shutdown of the buffers
			}

			if ($socket !== $this->_socket)
			{
				$this->writeBuffer = $this->readBuffer = "";
				$this->writeBufferSize = $this->readBufferSize = 0;
			}

			$this->_socket = $socket;
		}

		function processWriteBuffer()
		{
			$totalWrittenSize = 0;

			if (!$this->writeBufferSize)
				return 0;

			while ($this->writeBufferSize)
			{
				$thisPacketSize = 2048;
				if ($thisPacketSize > $this->writeBufferSize)
					$thisPacketSize = $this->writeBufferSize;

				$sizeWritten = fwrite($this->socket, $this->writeBuffer, $thisPacketSize);
				if ($sizeWritten === FALSE)
				{
					\Novae\Log::error("Failed to write ".$thisPacketSize." bytes, aborting connection");
					$this->abort();
					throw new Exception("Connection aborted due to write failure");
				}

				if ($sizeWritten == 0)
				{	// this isn't an error, it is normal.   That's why we buffer writes!
					\Novae\Log::debug("Write blocked", "fwrite returned 0 while there was still ".$this->writeBufferSize." bytes waiting to be written");
					break;
				}
//else \Novae\Log::debug("write", ["msg sent" => substr($this->writeBuffer, 0, $sizeWritten)]);
				$this->writeBuffer = substr($this->writeBuffer, $sizeWritten);
				$this->writeBufferSize -= $sizeWritten;
				$totalWrittenSize += $sizeWritten;
			}

			return $totalWrittenSize;
		}

		function abort()
		{
			if (!$this->socket)
				return FALSE;

			fclose($this->socket);
			$this->socket = FALSE;
			// not clearing the read/write buffer, so that it can be inspected, and, queued messages can be processed
			// the buffers will be cleared upon reconnect
			return TRUE; // successfully aborted
		}

		function readToBuffer()
		{
			if (!$this->socket)
{
				throw new \ToDo(); // ToDo: replace with a not connected exception when our exception framework exists
}
			while (!feof($this->socket))
			{
				if (($newBuffer = fread($this->socket, 2048)) !== FALSE)
				{
					if (($size = strlen($newBuffer)))
					{
						$this->readBuffer .= $newBuffer;
						$this->readBufferSize += $size;
					}
					else
						break;
				}
				else
				{
					\Novae\Log::error("Socket read error", "An error occurred trying to read from the socket", $this);
				}
			}
		}


		function processReadBuffer($once=FALSE)
		{
			$receivedMessage = 0;

			/* temp code that just delineates each message at \n */
			while ($this->readBufferSize &&
				($pos = strpos($this->readBuffer, "\n")) !== FALSE)
			{

				$message = substr($this->readBuffer, 0, $pos);
				$this->readBuffer = substr($this->readBuffer, $pos+1);
				$this->readBufferSize -= ($pos+1);


				$this->emit("socket-receive-message", [ "message" => $message ]);

				if ($once)
					return $message;

				$receivedMessage++;
			}

			return $receivedMessage;
		}

		/** process the Write and Read buffers.  If a timeout has been specified,
		then the Write and Read buffers will be processed until that timeout has elapsed.

		If a Trap is provided for $workTimeout, then the Write/Read buffers will process
		as long as that trap has not been triggered

		Providing a workTimeout and no writeTimeout causes processing for the duration of the workTimeout, even if the read and write buffers are empty, in case a new read produces data in to the read buffer
		Providing only a writeTimeout causes the work to continue until the write buffer is empty, or the timeout has expired
		Providing both a writeTimeout and a workTimeout causes both to combine;  either no writable data for writeTimeout, or no work in the buffers to process, causes a timeout.

		@param $workTimeout=null [ null, int or instanceOf Event\Trap ]
		@param $writeTimeout=null [ null or int ] If provided, processing will end once the write buffer has not been modified for this amount of seconds
		*/ // ToDo:  reconsider argument format, i.e. [ read => 30 ],   [ write => 30 ], [  timeout => 30, write => 30 ]
		function processBuffers( $workTimeout = null, $writeTimeout = null )
		{
			$eventLoopStart = $lastWriteTime = microtime(TRUE);
			// allow final processing of events until we are in sync,
			// in-case a socket-disconnect-request handler fires messages.
			do
			{
				$wbSize = $this->writeBufferSize;
				$rbSize = $this->readBufferSize;

				$wroteFromBuffer = $readFromBuffer = $readToBuffer = FALSE;

				if ($this->writeBufferSize)
				{
					$this->processWriteBuffer();
					if ($wbSize != $this->writeBufferSize)
					{
						$wroteFromBuffer = TRUE;
						if ($writeTimeout)
						{
							$lastWriteTime = microtime(TRUE);
						}
					}
				}

				$this->readToBuffer(); // processes the socket reads in to the read buffer; this is non-blocking
				if ($rbSize != $this->readBufferSize)
				{
					$readToBuffer = TRUE;
					$rbSize = $this->readBufferSize;
				}

				if ($this->readBufferSize)
				{
					$this->processReadBuffer();
					if ($this->readBufferSize != $rbSize)
						$readFromBuffer = TRUE;
				}

				$now = microtime(TRUE);

				if (!is_null($workTimeout) && $now >= $eventLoopStart + $workTimeout)
					break;

				if ($writeTimeout && $now >= $lastWriteTime + $writeTimeout)
					break;

				if ($writeTimeout && !$this->writeBufferSize && is_null($workTimeout))
					break;

				$workComplete = $wroteFromBuffer /* we sent a message to the remote end */
					|| $readToBuffer /* we received a message from the remote end (queued to the buffer) */
					|| $readFromBuffer; /* we read a message from the buffer and processed it */

				$workPending = $this->writeBufferSize || $this->readBufferSize;

				if ($workPending && !$workComplete) // all pending work was blocked on this iteration
					usleep(50000); // ToDo: change to a stream_select, with a precise timeout

			} while ($workPending || $workComplete);

			return;
		}
		/** disconnects from the attached socket, emitting socket-disconnect-request in
		advance, and then processing events for $disconnectTimeout [m]seconds afterwards,
		and finally emitting socket-disconnected as confirmation.

		One full read/write loop will always be completed regardless of the timeout requested
		(if a read causes events to be fired which take greater than the disconnectTimeout
		to process, then, the event will not forcibly break, nor will the processing of any
		further events which have already been read.  )

		@param $disconnectTimeout=2 0/FALSE to not process the socket further, numeric for a timeout.  Max 30 (30 seconds)
		*/
		function disconnect($disconnectTimeout = 2)
		{
			if (!$this->socket)
				throw new \ToDo();//Replace this with a socket-not-connected-like exception when the exception system is in place

			if ($disconnectTimeout > 30)
			{
				\Novae\Log::warn("Invalid parameter", "Socket::disconnect received an invalid disconnectTimeout; resetting it to the maximum of 30", $this, [ "requested disconnectTimeout" => $disconnectTimeout]);
				$disconnectTimeout = 30;
			}

			if (!$disconnectTimeout) // $instant tells us to not communicate further with the socket
				stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);

			$this->emit("socket-disconnect-request", [ "disconnectTimeout" => $disconnectTimeout ]);

			if ($disconnectTimeout)
			{
				$this->processBuffers( null, $disconnectTimeout );
			}

			fclose($this->socket);
			$this->_socket = null;

			$this->emit("socket-disconnected");
		}
	}
