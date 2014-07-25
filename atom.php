<?php

	header( 'Content-Type: text/xml' );

	error_reporting(-1);
	ini_set('display_errors', true);
	date_default_timezone_set('UTC');

	$expiration = 6 * 60 * 60;		// 6 hours

	if ( file_exists( 'cache/atom.cache' ) ) {
		$mtime = filemtime( 'cache/atom.cache' );
		if ( time() < $mtime + $expiration ) {
			echo file_get_contents( 'cache/atom.cache' );
			die();
		}
	}

	require('comediansincarsgettingcoffee.php');

	$videos = ComediansInCarsGettingCoffee::get_videos();
	
	// there are two different types of videos, so let's use our helper method to combine them
	$all_videos = ComediansInCarsGettingCoffee::combine_types( $videos );

	$dom = new DOMDocument('1.0', 'utf-8');
	$dom->formatOutput = true;

	// create the root feed node with its namespace
	$feed = $dom->createElementNS( 'http://www.w3.org/2005/Atom', 'feed' );

	// create the title node
	$title_node = $dom->createTextNode( 'Comedians in Cars Getting Coffee' );
	$title = $dom->createElement( 'title' );
	$title->appendChild( $title_node );

	// add the title to the feed node
	$feed->appendChild( $title );

	// and the link node
	$link = $dom->createElement( 'link' );
	$link->setAttribute( 'href', 'http://comediansincarsgettingcoffee.com' );

	// add it to the feed node
	$feed->appendChild( $link );

	// add the "required" "self" link node

	// first we have to figure out what the URL actually is
	$self_proto = ( isset( $_SERVER['HTTPS'] ) && !empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off' ) ? 'https://' : 'http://';
	$self_host = ( isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : ( isset( $_SERVER['SERVER_NAME'] ) ? $_SERVER['SERVER_NAME'] : '' ) );	// HTTP_HOST is not set for HTTP/1.0 requests
	$self_url = $self_proto . $self_host . ( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '' );
	$self_link = $dom->createElement( 'link' );
	$self_link->setAttribute( 'href', $self_url );
	$self_link->setAttribute( 'rel', 'self' );

	// and add it to the feed
	$feed->appendChild( $self_link );

	// figure out the last updated date - should be the date of the first item in the list
	if ( count( $all_videos ) > 0 ) {
		$last_updated = new DateTime( round( $all_videos[0]->pubDate ) );
	}
	else {
		// otherwise, it's now - we just checked
		$last_updated = new DateTime();
	}

	$updated = $dom->createElement( 'updated', $last_updated->format( DateTime::ATOM ) );

	$feed->appendChild( $updated );

	$author = $dom->createElement( 'author' );
	$author_name = $dom->createElement( 'name', 'Comedians in Cars Getting Coffee' );

	$author->appendChild( $author_name );

	$feed->appendChild( $author );

	$uuid = hash( 'md5', 'Comedians in Cars Getting Coffee' );		// md5 so we get 32 chars back
	$uuid_hex = uuid_hex( $uuid );

	$id = $dom->createElement( 'id', 'urn:uuid:' . $uuid_hex );

	$feed->appendChild( $id );

	$i = 0;
	foreach ( $all_videos as $item ) {

		$entry = $dom->createElement( 'entry' );

		$title_node = $dom->createTextNode( $item->title );
		$title = $dom->createElement('title');
		$title->appendChild( $title_node );

		$link = $dom->createElement( 'link' );
		$link->setAttribute( 'href', 'http://comediansincarsgettingcoffee.com/' . $item->urlSlug );

		$uuid = hash( 'md5', $item->mediaId );
		$uuid_hex = uuid_hex( $uuid );
		$id = $dom->createElement( 'id', 'urn:uuid:' . $uuid_hex );

		$updated_date = new DateTime( $item->pubDate );

		$updated = $dom->createElement( 'updated', $updated_date->format( DateTime::ATOM ) );

		$guests = array();
		foreach ( $item->guests as $guest ) {
			$guests[] = $guest->name;
		}
		
		if ( count( $guests ) > 1 ) {
			$last_guest = array_pop( $guests );
			
			$guests = implode( $guests, ', ' );
			$guests = $guests . ', and ' . $last_guest;
		}
		else {
			$guests = implode( $guests, ', ' );
		}

		$description = sprintf(
			'New %1$s with guest %2$s' . "\n\n" . '%3$s',
			ucwords( $item->type ),
			$guests,
			$item->description
		);

		$summary_node = $dom->createTextNode( $description  );
		$summary = $dom->createElement( 'summary' );
		$summary->appendChild( $summary_node );
		$summary->setAttribute( 'type', 'html' );
		
		$thumbnail = $dom->createElementNS( 'http://search.yahoo.com/mrss', 'media:thumbnail' );
		$thumbnail->setAttribute( 'url', $item->images->thumb );

		$entry->appendChild( $title );
		$entry->appendChild( $link );
		$entry->appendChild( $id );
		$entry->appendChild( $updated );
		$entry->appendChild( $summary );
		$entry->appendChild( $thumbnail );

		$feed->appendChild( $entry );

		$i++;

	}

	// add the root feed node to the document
	$dom->appendChild( $feed );

	$xml = $dom->saveXML();

	file_put_contents( 'cache/atom.cache', $xml );

	echo $xml;

	function uuid_hex ( $uuid ) {
		$uuid = str_split( $uuid );
		$uuid_hex = '';
		for ( $i = 0; $i < 32; $i++ ) {
			if ( $i == 8 || $i == 12 || $i == 16 || $i == 20 ) {
				$uuid_hex .= '-';
			}
			$uuid_hex .= $uuid[ $i ];
		}

		return $uuid_hex;
	}


?>