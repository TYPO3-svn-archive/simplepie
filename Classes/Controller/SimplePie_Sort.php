<?
class Tx_Simplepie_Controller_FeedController_SimplePie_Sort extends SimplePie {
	
	static function compareDesc($a, $b) {
		$ats = $a->get_date();
		$bts = $b->get_date();

		if ($ats == $bts)
			return 0;
		if ($ats > $bts)
			return -1;
		if ($ats < $bts)
			return +1;
	}

	static function compareAsc($a, $b) {
		$ats = $a->get_date();
		$bts = $b->get_date();

		if ($ats == $bts)
			return 0;
		if ($ats < $bts)
			return -1;
		if ($ats > $bts)
			return +1;
	}
}
?>