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
		use ProviderTrait; // Event Provider trait, provides ::emit and ::on
	}

