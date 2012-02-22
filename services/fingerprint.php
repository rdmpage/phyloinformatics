<?php

// convert string to Google Refine-style finger print, based on 
// https://gist.github.com/1374639#file_fingerprint.rb
// by https://github.com/jpmckinney

function finger_print ($str)
{
	// Convert accented characters
	$str = strtr(utf8_decode($str), 
			utf8_decode("ÀÁÂÃÄÅàáâãäåĀāĂăĄąÇçĆćĈĉĊċČčÐðĎďĐđÈÉÊËèéêëĒēĔĕĖėĘęĚěĜĝĞğĠġĢģĤĥĦħÌÍÎÏìíîïĨĩĪīĬĭĮįİıĴĵĶķĸĹĺĻļĽľĿŀŁłÑñŃńŅņŇňŉŊŋÒÓÔÕÖØòóôõöøŌōŎŏŐőŔŕŖŗŘřŚśŜŝŞşŠšſŢţŤťŦŧÙÚÛÜùúûüŨũŪūŬŭŮůŰűŲųŴŵÝýÿŶŷŸŹźŻżŽž"),
			"aaaaaaaaaaaaaaaaaaccccccccccddddddeeeeeeeeeeeeeeeeeegggggggghhhhiiiiiiiiiiiiiiiiiijjkkkllllllllllnnnnnnnnnnnoooooooooooooooooorrrrrrsssssssssttttttuuuuuuuuuuuuuuuuuuuuwwyyyyyyzzzzzz");
		 
	$str = utf8_encode($str);
	
	// lowercase
	$str = strtolower($str);
	
	// normalise space
	$str = preg_replace('/\s\s+/', ' ', $str);
	
	// strip punctuation
	$str = preg_replace('/[,|\.|\(|\)|-]/', '', $str);

	// strip and|&
	$str = preg_replace('/ and /', ' ', $str);
	$str = preg_replace('/ & /', ' ', $str);

	return $str;
}

?>