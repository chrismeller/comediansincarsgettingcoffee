<?php
	
	class ComediansInCarsGettingCoffee {
		
		const URL = 'http://comediansincarsgettingcoffee.com';
		
		public static function get_videos ( ) {
	
			$contents = file_get_contents( self::URL );
	
			$dom = new \DOMDocument('1.0', 'utf-8');
	
			// we don't like some of the html
			@$dom->loadHTML( $contents );
	
			$xpath = new \DOMXPath( $dom );
	
			$data = $xpath->query( '//script[ @id="videoData" ]' );

			$data = $data->item(0);
	
			$json = json_decode( $data->nodeValue );
			
			return $json;
			
		}
		
		public static function combine_types ( $videos ) {
			
			$all_videos = array();
			
			foreach ( $videos->videos as $video ) {
				$all_videos[] = $video;
			}
			
			foreach ( $videos->singleshots as $video ) {
				$all_videos[] = $video;
			}
			
			// sort them all
			usort( $all_videos, function ( $a, $b ) {
				$a_date = new \DateTime( $a->pubDate );
				$b_date = new \DateTime( $b->pubDate );
				
				if ( $a_date == $b_date ) {
					return 0;
				}
				
				// note these are reversed, because we want to sort descending
				if ( $a_date < $b_date ) {
					return 1;
				}
				else {
					return -1;
				}
			} );
			
			return $all_videos;
			
		}
		
	}

?>