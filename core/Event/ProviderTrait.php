<?php
	namespace Novae\Event;

	/* ProviderTrait provides emit/on to CoreObject

		All methods here work statically or non-statically, intentionally, to reduce code duplication

		Events sent statically are only sent to static listeners,
		Events not sent statically, are only sent to non-static listeners (and only for the same instance)

		For example, consider a class "Person" that uses Novae\Event\ProviderTrait:

			Person::on("EventA", $callbackA1)
			Person::on("EventB", $callbackB1);

			$person->on("EventA", $callbackA2);
			$person->on("EventB", $callbackB2)

			Person::emit("EventA", ....);
			$person->emit("EventB", ....);

			EventA will only go to callbackA1,  because EventA was only emitted statically
			EventB will only go to callbackB1,  because EventB was not emitted statically
	*/


	trait ProviderTrait {
		static private $__providerTrait_subscriptions_static = []; // Cheap (i.e. todo:refactor) way to pair listeners and filters  ( [ filter is Novae\Event\Filter,  listener is \Novae\Event\Listener])
		private $__providerTrait_subscriptions = [];

		public function emit( ...$event )
		{
			if (!$event)
				return FALSE;

			$event = self::ensureEventObject($event);

			if (isset($this))
				$event->self = $this;

			$stoppable = is_subclass_of($event, '\PSR\EventDispatcher\StoppableEventInterface');
			if ($stoppable && $event->isPropagationStopped()) /* no need to check filters */
				return $event;

			if (isset($this))
				$listeners = $this->getListenersForEvent($event);
			else
				$listeners = self::getListenersForEvent($event);

//var_dump(["listeners found for emit" => $listeners]);
			foreach($listeners as $listener)
			{
				if ($stoppable && $event->isPropagationStopped())
					return;

				$listener($event); // PSR-14 requires we don't catch exceptions that occur here - why?
			}
//var_Dump(["Finished emitting event to ".count($listeners)." listener(s)" => $event]);

			return $event;
		}

		static private function ensureEventObject($event)
		{
			if ($event && is_subclass_of($event[0], '\Novae\Event\EventInterface'))
			{
				if (count($event) != 1) // Replace with a misuse exception when the exception system exists
					throw new ToDoException("Stream::emit can't accept a event object as well as additinonal arbitrary event details");
				$event = $event[0];
			}
			else // Stream::emit("foo", [ bar => $baz]) -> Stream::emit(new \Novae\Event("foo", [bar => $baz]))
			{
				$event = new \Novae\Event(...$event);
			}

			return $event;
		}

		public function getListenersForEvent(...$event)
		{
			$event = self::ensureEventObject($event);

			$listeners = [];

			if (isset($this))
				$subscriptions = $this->__providerTrait_subscriptions;
			else
				$subscriptions = self::$__providerTrait_subscriptions_static;

			foreach($subscriptions as $listenerData)
			{
				if ($listenerData["filter"] && $listenerData["filter"]->verify($event))
					$listeners[] = $listenerData["listener"];
			}

			return $listeners;
		}


		/**
		Registers an event listener to the event stream.  This can be called statically, or on
		an instance of a class that implements this trait.   Subscriptions to events that
		are registered statically will only fire for events emitted statically, and vice versa.

		ex:
		    Novae\Event\Stream::on([ \Novae\Event\Filter filter, ] callable $listener)
		    $instance->on([ \Novae\Event\Filter filter, ], callable $listener )

		@param filter The \Nova\Event\Filter to apply to events, which if matched against an emitted event, will trigger the listener
		@param listener The callable that will be called with the event, when an event matching the provided filter is matched
		@returns \Event\Listener  A new EventListener is returned, which is subscribed


		*/
		public function on( $filter="__ANY__", callable $callback)
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

			if (isset($this))
				$this->__providerTrait_subscriptions[] = $newListenerEntry;
			else
				self::$__providerTrait_subscriptions_static[] = $newListenerEntry;

			return $listener;
		}
	}
