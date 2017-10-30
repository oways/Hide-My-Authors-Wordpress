<?php
   /*
   Plugin Name: Hide My Authors
   Description: a plugin to hide the author pages which expose the user's username which can be used for some attacks
   Version: 0.1
   Author: Oways
   Author URI: https://twitter.com/0w4ys
   License: GPL2
   */

if (defined( 'ABSPATH' )) {

		function insert_with_markers_on_top( $filename, $marker, $insertion)
                {
                    
                    if ( ! file_exists( $filename ) ) {
                        if ( ! is_writable( dirname( $filename ) ) ) {
                            return false;
                        }
                        if ( ! touch( $filename ) ) {
                            return false;
                        }
                    } elseif ( ! is_writeable( $filename ) ) {
                        return false;
                    }

                    if ( ! is_array( $insertion ) ) {
                        $insertion = explode( "\n", $insertion );
                    }

                    $start_marker = "# BEGIN {$marker}";
                    $end_marker   = "# END {$marker}";

                    $fp = fopen( $filename, 'r+' );
                    if ( ! $fp ) {
                        return false;
                    }

                    // Attempt to get a lock. If the filesystem supports locking, this will block until the lock is acquired.
                    flock( $fp, LOCK_EX );

                    $lines = array();
                    while ( ! feof( $fp ) ) {
                        $lines[] = rtrim( fgets( $fp ), "\r\n" );
                    }

                    // Split out the existing file into the preceding lines, and those that appear after the marker
                    $pre_lines = $post_lines = $existing_lines = array();
                    $found_marker = $found_end_marker = false;
                    foreach ( $lines as $line ) {
                        if ( ! $found_marker && false !== strpos( $line, $start_marker ) ) {
                            $found_marker = true;
                            continue;
                        } elseif ( ! $found_end_marker && false !== strpos( $line, $end_marker ) ) {
                            $found_end_marker = true;
                            continue;
                        }
                        if ( ! $found_marker ) {
                            $pre_lines[] = $line;
                        } elseif ( $found_marker && $found_end_marker ) {
                            $post_lines[] = $line;
                        } else {
                            $existing_lines[] = $line;
                        }
                    }

                    // Check to see if there was a change
                    if ( $existing_lines === $insertion ) {
                        flock( $fp, LOCK_UN );
                        fclose( $fp );

                        return true;
                    }

                    
                    // Generate the new file data
                    if($found_marker && $found_end_marker)
                        {
                            $new_file_data = implode( "\n", array_merge(
                                $pre_lines,
                                array( $start_marker ),
                                $insertion,
                                array( $end_marker ),
                                $post_lines
                            ) );
                        }
                        else
                        {
                            
                            $new_file_data = implode( "\n", array_merge(
                                array( $start_marker ),
                                $insertion,
                                array( $end_marker ),
                                $pre_lines
                            ) );        
                            
                        }

                    // Write to the start of the file, and truncate it to that length
                    fseek( $fp, 0 );
                    $bytes = fwrite( $fp, $new_file_data );
                    if ( $bytes ) {
                        ftruncate( $fp, ftell( $fp ) );
                    }
                    fflush( $fp );
                    flock( $fp, LOCK_UN );
                    fclose( $fp );

                    return (bool) $bytes;    
                    
                    
                }
    add_action( 'plugins_loaded', 'Hide_My_Authors'); 
    function Hide_My_Authors() 
        {

// Get path to main .htaccess for WordPress
$htaccess = ABSPATH.".htaccess";
$lines = array("RewriteEngine On",
"RewriteCond %{REQUEST_URI} ^/author [NC]",
"RewriteRule ^.*$ /index.php [R=302,L]");

insert_with_markers_on_top($htaccess, "Hide My Authors", $lines);
}
}
