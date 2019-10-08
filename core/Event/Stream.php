<?php
	namespace Novae\Event;

	/** Unified Event Stream
	static singleton (  Stream::emit(... event data ...)  )

	Primary interface:
		emit(..$event | $event)	Emits an event, which either must implement Novae\EventInterface,
				or else will be arbitrary data that is turned in to a \Novae\Event

		on( event description, callable listener)
			returns \Novae\Event\Listener
	*/
	class Stream implements ProviderInterfaceStatic {
		static private $subscriptions = []; // Cheap (i.e. todo:refactor) way to pair listeners and filters  ( [ filter is Novae\Event\Filter,  listener is \Novae\Event\Listener])

		static public function emit( ...$event )
		{
			if (!$event)
				return FALSE;

			$event = self::ensureEventObject($event);

			$stoppable = is_subclass_of($event, '\PSR\EventDispatcher\StoppableEventInterface');
			if ($stoppable && $event->isPropagationStopped()) /* no need to check filters */
				return $event;

			$listeners = self::getListenersForEvent($event);
var_dump(["listeners found for emit" => $listeners]);
			foreach($listeners as $listener)
			{
				if ($stoppable && $event->isPropagationStopped())
					return;

				$listener($event);
			}
var_Dump(["Finished emitting event to ".count($listeners)." listener(s)" => $event]);
		}

		static private function ensureEventObject($event)
		{
			if ($event && is_subclass_of($event[0], '\Novae\Event\EventInterface'))
			{
				if (count($event) != 1) // Replace with a misuse exception when the exception system exists
					throw new ToDoException("Stream::emit can't accept a event object as well as additinonal arbitrary event details");
				$event = $event[0];
			}
			else // Stream::emit("foo", [ bar => $baz]) -> Stream::emit(new Event("foo", [bar => $baz]))
			{
				$event = new Event(...$event);
			}

			return $event;
		}

		static public function getListenersForEvent(...$event)
		{
			$event = self::ensureEventObject($event);

			$listeners = [];

			foreach(self::$subscriptions as $listenerData)
			{
				if ($listenerData["filter"] && $listenerData["filter"]->verify($event))
					$listeners[] = $listenerData["listener"];
			}

			return $listeners;
		}


		/** Novae\Event\Stream::on([ \Novae\Event\Filter filter, ] callable $listener)
		@param filter The \Nova\Event\Filter to apply to events, which if matched against an emitted event, will trigger the listener
		@param listener The callable that will be called with the event, when an event matching the provided filter is matched
		@returns \Event\Listener  A new EventListener is returned, which is subscribed

		Registers an event listener to the event stream

		*/
		static public function on( $filter="__ANY__", callable $callback)
		{
			if (is_subclass_of($callback, '\Novae\Event\Listener'))
				$listener = clone $callback;
			else
				$listener = new Listener($callback);

			$newListenerEntry = [
				"listener" => $listener,
			];

			if ($filter !== "__ANY__")
			{
				if (!is_object($filter) || !is_subclass_of($filter, '\Novae\Event\Filter'))
					$filter = new Filter($filter);

				$newListenerEntry["filter"] = $filter;
			}

			self::$subscriptions[] = $newListenerEntry;

			return $listener;
		}

	}

