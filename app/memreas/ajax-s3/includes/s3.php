<?php
class S3 {
	public static $AWS_ACCESS_KEY 			= 'AKIAJMXGGG4BNFS42LZA';
	public static $AWS_SECRET_ACCESS_KEY 	= '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';

	/*
	 * Purpose:
	 * 		Actionscript encodes '+' characters in the signature incorrectly - it makes
	 * 		them a space instead of %2B the way PHP does. This causes uploadify to error
	 * 		out on upload. This function recursively generates a new policy and signature
	 * 		until a signature without a + character is created.
	 * Accepts: array $data
	 * Returns: policy and signature
	 */
	public static function get_policy_and_signature( array $data )
	{
		$policy = self::get_policy_doc( $data );
		$signature = self::get_signature( $policy );

		if ( strpos($signature, '+') !== FALSE )
		{
			$data['timestamp'] = intval(@$data['timestamp']) + 1;
			return self::get_policy_and_signature( $data );
		}

		return array($policy, $signature);
	}

	public static function get_policy_doc(array $data)
	{
		return base64_encode(
			'{'.
				'"expiration": "'.gmdate('Y-m-d\TH:i:s\Z', time()+60*60*24+intval(@$data['timestamp'])).'",'.
				'"conditions": '.
				'['.
					'{"bucket": "'.$data['bucket'].'"},'.
					'["starts-with", "$key", ""],'.
					'{"acl": "authenticated-read"},'.
					//'{"success_action_redirect": "'.$SWFSuccess_Redirect.'"},'.
					'{"success_action_status": "201"},'.
					'["starts-with","$key","'.str_replace('/', '\/', $data['folder'] ).'"],'.
					'["starts-with","$Filename",""],'.
					'["starts-with","$folder",""],'.
					'["starts-with","$fileext",""],'.
					'["content-length-range",0,5242880]'.
				']'.
			'}'
		);
	}

	public static function get_signature( $policy_doc ) {
		return base64_encode(hash_hmac(
			'sha1', $policy_doc, self::$AWS_SECRET_ACCESS_KEY, true
		));
	}
}