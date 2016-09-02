<?php

	require_once( 'prpc2/client.php' );

	switch( @$_SERVER['VMODE'] )
	{
		case 'live':
		{
			$post_url = 'prpc://reportpro.epointmarketing.com/service/scrub_collect.php';
			break;
		}
		case 'rc':
		{
			$post_url = 'prpc://rc.reportpro.epointmarketing.com/service/scrub_collect.php';
			break;
		}
		default: //Dev
		{
			$post_url = 'prpc://rpv2.area51.tss/service/scrub_collect.php';
			break;
		}
	}

	/**
	 * @param string $line
	 * @return array
	 */
	function extract_data( $line )
	{
		$parts = explode( '/opt/statpro/var/', $line );

		if( false === isset( $parts[1] ) )
		{
			$parts[1] = '';
		}

		return array( 'size' => trim( $parts[0] ), 'path' => $parts[1] );
	}

	// This is the magic part
	$output = `du /opt/statpro/var/`;

	$line_array = explode( "\n", $output );

	$complied_array = array();
	foreach( array_map( 'extract_data', $line_array ) as $dir )
	{
		if( '' == $dir['size'] ) // Last line
		{
			continue;
		}

		$parts = explode( '/', $dir['path'] );

		$customer = $parts[0];
		if( '' == $customer )
		{
			$customer = 'total';
		}

		$sub_dir = 'total';
		if( isset( $parts[1] ) )
		{
			$sub_dir = $parts[1];
		}

		if( false == isset( $complied_array[ $customer ] ) )
		{
			$complied_array[ $customer ] = array( 'total' => 0, 'journal' => 0, 'repository' => 0 );
		}
		$complied_array[ $customer ][ $sub_dir ] = $dir['size'];
	}

	// Send it to ReportPro
	$server = new Prpc_Client2( $post_url );
	$server->recordSizeMetrics( `hostname -f`, $complied_array );
?>