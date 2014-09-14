<?php

class VideoEpisodeCollection extends \VideoCollection {

	protected static $_schema = array(
		'@extends' => 'VideoEpisode',
	);

	public static function getDbCollectionName($modelClassname=null) {
		return 'VideoCollection';
	}
}
