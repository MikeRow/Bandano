<?php

	namespace php4nano\lib\NanoTools;
	
	require_once __DIR__ . '/../lib3/RaiBlocksPHP-master/util.php';
	require_once __DIR__ . '/../lib3/RaiBlocksPHP-master/Salt/autoload.php';
	
	use \Uint as Uint;
	use \SplFixedArray as SplFixedArray;
	use \Blake2b as Blake2b;
	use \Salt as Salt;
	use \FieldElement as FieldElement;
	use \hexToDec as hexToDec;
	use \decToHex as decToHex;
	
	class NanoTools
	{
		// Denominations and raw values
	
		const raw2 =
		[
			'unano' => '1000000000000000000',
			'mnano' => '1000000000000000000000',
			 'nano' => '1000000000000000000000000',
			'knano' => '1000000000000000000000000000',
			'Mnano' => '1000000000000000000000000000000',
			 'NANO' => '1000000000000000000000000000000',
			'Gnano' => '1000000000000000000000000000000000'
		];	
	
	
	
		// ***************************
		// *** Denomination to raw ***
		// ***************************
	
	
	
		public static function den2raw( $amount, string $denomination )
		{
			$raw2denomination = self::raw2[$denomination];
			
			if( $amount == 0 )
			{
				return '0';
			}
			
			if( strpos( $amount, '.' ) )
			{
				$dot_pos = strpos( $amount, '.' );
				$number_len = strlen( $amount ) - 1;
				$raw2denomination = substr( $raw2denomination, 0, - ( $number_len - $dot_pos ) );
			}
			
			$amount = str_replace( '.', '', $amount ) . str_replace( '1', '', $raw2denomination );
			
			// Remove useless zeroes from left
			
			while( substr( $amount, 0, 1 ) == '0' )
			{
				$amount = substr( $amount, 1 );	
			}
			
			return $amount;
		}
	
	
	
		// ***************************
		// *** Raw to denomination ***
		// ***************************
		
		
		
		public static function raw2den( $amount, string $denomination )
		{
			$raw2denomination = self::raw2[$denomination];
			
			if( $amount == '0' )
			{
				return 0;
			}
			
			$prefix_lenght = 39 - strlen( $amount );
			
			$i = 0;
			
			while( $i < $prefix_lenght )
			{
				$amount = '0' . $amount;
				$i++;
			}
			
			$amount = substr_replace( $amount, '.', - ( strlen( $raw2denomination ) - 1 ), 0 );
		
			// Remove useless zeroes from left
		
			while( substr( $amount, 0, 1 ) == '0' && substr( $amount, 1, 1 ) != '.' )
			{
				$amount = substr( $amount, 1 );	
			}
		
			// Remove useless decimals
		
			while( substr( $amount, -1 ) == '0' )
			{
				$amount = substr( $amount, 0, -1 );	
			}
			
			// Remove dot if all decimals are zeroes
			
			if( substr( $amount, -1 ) == '.' )
			{
				$amount = substr( $amount, 0, -1 );	
			}	
		
			return $amount;
		}
		
		
		
		// ************************************
		// *** Denomination to denomination ***
		// ************************************
		
		
		
		public static function den2den( $amount, string $denomination_from, string $denomination_to )
		{
			$raw = self::den2raw( $amount, $denomination_from );
			
			return self::raw2den( $raw, $denomination_to );
		}
		
		
		
		// *****************************
		// *** Account to public key ***
		// *****************************
		
		
		
		public static function account2public( string $account )
		{
			if( ( strpos( $account, 'xrb_1' ) === 0 || strpos( $account, 'xrb_3' ) === 0 || strpos( $account, 'nano_1' ) === 0 || strpos( $account, 'nano_3' ) === 0 ) && ( strlen( $account ) == 64 || strlen( $account ) == 65 ) )
			{
				$crop = explode( '_', $account );
				$crop = $crop[1];
				
				if( preg_match( '/^[13456789abcdefghijkmnopqrstuwxyz]+$/', $crop ) )
				{
					$aux = Uint::fromString( substr( $crop, 0, 52 ) )->toUint4()->toArray();
					array_shift( $aux );
					$key_uint4 = $aux;
					$hash_uint8 = Uint::fromString( substr( $crop, 52, 60 ) )->toUint8()->toArray();
					$key_uint8 = Uint::fromUint4Array( $key_uint4 )->toUint8();
					
					$key_hash = new SplFixedArray( 64 );
					
					$b2b = new Blake2b();
					$ctx = $b2b->init( null, 5 );
					$b2b->update( $ctx, $key_uint8, count( $key_uint8 ) );
					$b2b->finish( $ctx, $key_hash );
					
					$key_hash = array_reverse( array_slice( $key_hash->toArray(), 0, 5 ) );
					
					if( $hash_uint8 == $key_hash )
					{
						return Uint::fromUint4Array( $key_uint4 )->toHexString();
					}
				}
			}
			
			return false;
		}
		
		
		
		// *****************************
		// *** Public key to account ***
		// *****************************
		
		
		
		public static function public2account( string $pk )
		{
			if( !preg_match( '/[0-9A-F]{64}/i', $pk ) ) return false;
			
			$key = Uint::fromHex( $pk );
			$checksum;
			$hash = new SplFixedArray( 64 );
			$b2b = new Blake2b();
			$ctx = $b2b->init( null, 5 );
			$b2b->update( $ctx, $key->toUint8(), 32 );
			$b2b->finish( $ctx, $hash );
			$hash = Uint::fromUint8Array( array_slice( $hash->toArray(), 0, 5 ) )->reverse();
			
			$checksum = $hash->toString();
			$c_account = Uint::fromHex( '0' . $pk )->toString();
			
			return 'nano_' . $c_account . $checksum;
		}
		
		
		
		// *********************************
		// *** Private key to public key ***
		// *********************************
		
		
		
		public static function private2public( string $sk )
		{
		    if( !preg_match( '/[0-9A-F]{64}/i', $sk ) ) return false;
		    
		    $salt = Salt::instance();
		    
		    $sk = Uint::fromHex( $sk )->toUint8();
			$pk = $salt::crypto_sign_public_from_secret_key( $sk );
			
			return Uint::fromUint8Array( $pk )->toHexString();
		}
		
		
		
		// ************************
		// *** Account validate ***
		// ************************
		
		
		
		public static function account_validate( string $account )
		{
			if( ( strpos( $account, 'xrb_1' ) === 0 || strpos( $account, 'xrb_3' ) === 0 || strpos( $account, 'nano_1' ) === 0 || strpos( $account, 'nano_3' ) === 0 ) && ( strlen( $account ) == 64 || strlen( $account ) == 65 ) )
			{
				$crop = explode( '_', $account );
				$crop = $crop[1];
				
				if( preg_match( '/^[13456789abcdefghijkmnopqrstuwxyz]+$/', $crop ) )
				{
					$aux = Uint::fromString( substr( $crop, 0, 52 ) )->toUint4()->toArray();
					array_shift( $aux );
					$key_uint4 = $aux;
					$hash_uint8 = Uint::fromString( substr( $crop, 52, 60 ) )->toUint8()->toArray();
					$key_uint8 = Uint::fromUint4Array( $key_uint4 )->toUint8();
					
					$key_hash = new SplFixedArray( 64 );
					
					$b2b = new Blake2b();
					$ctx = $b2b->init( null, 5 );
					$b2b->update( $ctx, $key_uint8, count( $key_uint8 ) );
					$b2b->finish( $ctx, $key_hash );

					$key_hash = array_reverse( array_slice( $key_hash->toArray(), 0, 5 ) );
					
					if( $hash_uint8 == $key_hash )
					{
						return true;
					}
				}
			}
			
			return false;
		}
		
		
		
		// ****************
		// *** Get keys ***
		// ****************
		
		
		
		public static function keys( bool $get_account = false )
		{
			$salt = Salt::instance();
			$keys = $salt->crypto_sign_keypair();
			$keys[0] = Uint::fromUint8Array( array_slice( $keys[0]->toArray(), 0, 32 ) )->toHexString();
			$keys[1] = Uint::fromUint8Array( $keys[1] )->toHexString();
			
			if( $get_account ) $keys[2] = self::public2account( $keys[1] );
			
			return $keys;
		}
		
		
		
		// ****************
		// *** Get seed ***
		// ****************
		
		
		
		public static function seed()
		{
			$salt = Salt::instance();
			
			$sk = FieldElement::fromString( Salt::randombytes() );
			$sk->setSize( 64 );
			$sk = Uint::fromUint8Array( array_slice( $sk->toArray(), 0, 32 ) )->toHexString();
            
			return $sk;
		}
		
		
		
		// **************************
		// *** Get keys from seed ***
		// **************************
		
		
		
		public static function seed2keys( string $seed, int $index = 0, bool $get_account = false )
		{
			$seed = Uint::fromHex( $seed )->toUint8();
			$index = Uint::fromDec( $index )->toUint8()->toArray();
			
			if( count( $index ) < 4 )
			{
				$missing_bytes = [];
				for ($i = 0; $i < ( 4 - count( $index ) ); $i++) $missing_bytes[] = 0;
				$index = array_merge( $missing_bytes, $index );
			}
			
			$index = Uint::fromUint8Array( $index )->toUint8();
			$sk = new SplFixedArray( 64 );
			
			$b2b = new Blake2b();
			$ctx = $b2b->init( null, 32 );
 			$b2b->update( $ctx, $seed, count( $seed ) );
			$b2b->update( $ctx, $index, 4 );
			$b2b->finish( $ctx, $sk );
            
			$sk = Uint::fromUint8Array( array_slice( $sk->toArray(), 0, 32 ) )->toHexString();
			$pk = self::private2public( $sk );
            
			$keys = [$sk,$pk];
			
			if( $get_account ) $keys[2] = self::public2account( $pk );
			
			return $keys;
		}
		
		
		
		// **********************
		// *** Sign a message ***
		// **********************
		
		
		
		public static function sign( $sk, $msg )
		{
			$salt = Salt::instance();
			$sk = FieldElement::fromArray(Uint::fromHex($sk)->toUint8());
			$pk = Salt::crypto_sign_public_from_secret_key($sk);
			$sk->setSize(64);
			$sk->copy($pk, 32, 32);
			$msg = Uint::fromHex($msg)->toUint8();
			$sm = $salt->crypto_sign($msg, count($msg), $sk);
			
			$signature = [];
			for($i = 0; $i < 64; $i++) $signature[$i] = $sm[$i];
			
			return Uint::fromUint8Array($signature)->toHexString();
		}
		
		
		
		// ****************************
		// *** Validate a signature ***
		// ****************************
		
		
		
		public static function sign_validate( $msg, $sig, $account )
		{
			$sig = Uint::fromHex($sig)->toUint8();
			$msg = Uint::fromHex($msg)->toUint8();
			$pk = Uint::fromHex(self::account2public($account))->toUint8();
			
			$sm = new SplFixedArray(64 + count($msg));
			$m = new SplFixedArray(64 + count($msg));
			for ($i = 0; $i < 64; $i++) $sm[$i] = $sig[$i];
			for ($i = 0; $i < count($msg); $i++) $sm[$i+64] = $msg[$i];
			
			return Salt::crypto_sign_open2($m, $sm, count($sm), $pk);
		}
		
		
		
		// ***********************
		// *** Generate a work ***
		// ***********************
		
		
		
		public static function work( string $hash, string $difficulty )
		{
			$hash = sodium_hex2bin( $hash );
			$difficulty = hexdec( $difficulty );
			
			$o = 1; $start = microtime( true );
			while( true )
			{
				$rng = random_bytes( 8 );

				$ctx = sodium_crypto_generichash_init( null, 16 );
				sodium_crypto_generichash_update( $ctx, $rng );
				sodium_crypto_generichash_update( $ctx, $hash );
				$work = sodium_crypto_generichash_final( $ctx );
				//echo strlen( $work ); exit;
				//$work = strrev( substr( $work, strlen( $work )-9, 8 ) );
				$work = sodium_bin2hex( $work );
				//echo strlen( $work ); exit;
				$work = strrev( substr( $work, 0, 16 ) );
				//$work = strrev( $work );
				
				$o++;
				if( hexdec( $work ) >= $difficulty )
				{
					echo number_format( $o / ( microtime( true ) - $start ), 0, '.', ',' ) . ' works/s'. PHP_EOL . number_format( $o, 0, '.', ',' ) . PHP_EOL . number_format( microtime( true ) - $start, 0, '.', ',' ) . ' s' . PHP_EOL;
					return $work;
				}
			}
		}
		
		
		
		// ***********************
		// *** Validate a work ***
		// ***********************
		
		
		
		public static function work_validate( string $hash, string $work, string $difficulty = null )
		{
			if( strlen( $work ) != 16 ) return false;
			if( strlen( $hash ) != 64 ) return false;
			if( !hex2bin( $hash ) ) return false;
			if( !hex2bin( $work ) ) return false;
				
			$res = new SplFixedArray( 64 );
			$workBytes = Uint::fromHex( $work )->toUint8();
			$hashBytes = Uint::fromHex( $hash )->toUint8();
			$workBytes = array_reverse( $workBytes->toArray() );
			$workBytes = SplFixedArray::fromArray( $workBytes );
			
			$blake2b = new Blake2b();
			$ctx = $blake2b->init( null, 8 );
			$blake2b->update( $ctx, $workBytes, 8 );
			$blake2b->update( $ctx, $hashBytes, 32 );
			$blake2b->finish( $ctx, $res );
			
			if( $res[7] == 255 )
				if( $res[6] == 255 )
					if( $res[5] == 255 )
						if( $res[4] >= 192 )
							return true;
			
			return false;
		}
	}

?>