<?php

class VideoEpisode extends \Video {

	protected static $_schema = array(
		'@@extends' => 'Video',
		'season',
		'episode',
	);

}
