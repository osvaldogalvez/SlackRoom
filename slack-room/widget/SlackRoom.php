<?php  
/**
 * 
 * 
 */
class slack_room_widget extends WP_Widget {
    private $user_list;
    private $all_emojis;
    function __construct() {
        parent::__construct(
        // widget ID
        'slack_room_widget',
        // widget name
        __('Slack Channel Widget', ' slack_room_widget_domain'),
        // widget description
        array( 'description' => __( 'Slack Room Widget', 'slack_room_widget_domain' ), )
        );
    }
    public function widget( $args, $instance ) {
        $title = apply_filters( 'widget_title', $instance['title'] );
        echo $args['before_widget'];
        //if title is present
        if ( ! empty( $title ) ){
            $img_slack = plugin_dir_url(dirname(__FILE__)).'assets/images/slack_icon.png';
            $img = "<img src='".$img_slack."'  width='50' /> ";
            echo $args['before_title'] . $img . $title . $args['after_title'];
        }
       
        //output
        $channel_id = $instance['channel'];
        $slack_history_count = isset($instance['channel_history_count']) ? $instance['channel_history_count']: '25';
        
        if ($channel_id) {
            $channel_history = $this->get_channel_history($channel_id, $slack_history_count);
            $this->user_list = $this->get_all_users();
            $this->all_emojis = $this->get_all_emojis();
            foreach ($channel_history as $message) {
                echo $this->render_message($message, $user_list);
            }
        }
        
        echo $args['after_widget'];
    }
    public function form( $instance ) {
        $channels = $this->get_channels();
        if ( isset( $instance[ 'title' ] ) )
            $title = $instance[ 'title' ];
        else
            $title = __( 'Default Title', 'slack_room_widget_domain' );
        $channel_history_count = $instance[ 'channel_history_count' ];
        
        
        ?>
        <p>
        <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <p>
        <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label></p>
        <select class="widefat" name="<?php echo $this->get_field_name( 'channel' ); ?>" id="<?php echo $this->get_field_id( 'channel' ); ?>">
        <?php foreach ($channels as $channel_options) { ?>
              <option value="<?php echo $channel_options['id'];?>" <?php echo ( isset( $instance[ 'channel' ] ) &&  $instance[ 'channel' ] === $channel_options['id']) ? "selected": "";?>><?php echo $channel_options['name'];?></option>
        <?php    }
        ?>
        </select>
        </p>
        <p>
        <label for="<?php echo $this->get_field_id( 'channel_history_count' ); ?>"><?php _e( 'Channel History Count:' ); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id( 'channel_history_count' ); ?>" name="<?php echo $this->get_field_name( 'channel_history_count' ); ?>" type="text" value="<?php echo esc_attr( $channel_history_count ); ?>" />
        </p>
    <?php
    }
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        $instance['channel'] = ( ! empty( $new_instance['channel'] ) ) ? strip_tags( $new_instance['channel'] ) : '';
        $instance['channel_history_count'] = ( ! empty( $new_instance['channel_history_count'] ) ) ? strip_tags( $new_instance['channel_history_count'] ) : '25';
        
        return $instance;
    }

    private function slack_api_request ( $apiPath, $postFields ) {
        
        $slackr_options = get_option("slackr_options");
        $postFields['token'] = (null !== $slackr_options["slackr_field_slack_api_token"] && !empty( $slackr_options["slackr_field_slack_api_token"] ) ) ? $slackr_options["slackr_field_slack_api_token"] : "";

       
        if(!$postFields['token'])
            return false;
        
        try {
            $ch = curl_init( 'https://slack.com/api/' . $apiPath );
            $data = http_build_query( $postFields );
            curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
            $result = curl_exec( $ch );
            curl_close($ch);
            
            $result = json_decode( $result, true );
        
            if ( $result['ok'] == '1' ) {
                return $result;
            }
            die( 'Could not execute request ' . $apiPath );
        } catch( Exception $e ) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }



    public function get_channels() {
        $all_channels = $this->slack_api_request('conversations.list', [
            'limit' => 500,
            'exclude_archived' => true
        ]);
    
        
        if ( $all_channels['channels'] )
            return $all_channels['channels'];
        return null;
    }

    private function get_all_emojis()
    {
        global $emojiCacheFilename;
        global $emojiCacheTimeout;

       /* $all_emojis = read_from_cache($emojiCacheFilename, $emojiCacheTimeout);
        if ($all_emojis) {
            return $all_emojis;
        }*/

        $all_emojis = $this->slack_api_request('emoji.list', [
            "channel" => $channelId,
        ]);

        $all_emojis = $all_emojis['emoji'];

        $standard_emojis = json_decode( file_get_contents( plugin_dir_url( dirname(__FILE__) )."assets/emojis.json", true ) );
        foreach( $standard_emojis as $e ) {
           
            $as_html = '';
            $us = explode( '-', $e->unified );
            $as_html = '';
            foreach ( $us as $u ) {
                $as_html .= '&#x' . $u . ';';
            }

            foreach( $e->short_names as $short_name ) {
                $all_emojis[$short_name] = $as_html;
            }
        }

        $all['slightly_smiling_face'] = 'alias:wink';
        $all['white_frowning_face'] = 'alias:sad';

        //write_to_cache($emojiCacheFilename, $all_emojis);

        return $all_emojis;
    }

    private function get_all_users()
    {
        global $userlistCacheFilename;
        global $userlistCacheTimeout;

        /*$userlist = read_from_cache($userlistCacheFilename, $userlistCacheTimeout);
        if ($userlist) {
            return $userlist;
        }
*/
        $userlist = $this->slack_api_request( 'users.list', [
            'limit' => 800,
            'presence' => false,
        ] );

        // Format in more sane way
        $userlistIndexed = [];
        foreach ( $userlist['members'] as $user ) {
            $userlistIndexed[$user['id']] = $user;
        }

       // write_to_cache($userlistCacheFilename, $userlistIndexed);
        
        return $userlistIndexed;
    }
    private function get_channel_history( $channelId, $history_count )
    {
        global $channelCacheFilename;
        global $channelCacheTimeout;


       /* $channel_history = read_from_cache($channelCacheFilename, $channelCacheTimeout);
        if ($channel_history) {
            return $channel_history;
        }*/

        $has_more = true;
        $channel_history = [];
        $fetch_from_ts = time();

        while ( $has_more && count( $channel_history ) < $history_count ) {
            $h = $this->slack_api_request( 'conversations.history', [
                'channel' => $channelId,
                'count' => 1,
                'latest' => $fetch_from_ts,
            ] );

            $channel_history = array_merge( $channel_history, $h['messages'] );
            
            $has_more = $h['has_more'];
            $fetch_from_ts = array_slice( $h['messages'], -1 )[0]['ts'];
        }

        //write_to_cache($channelCacheFilename, $channel_history);

        return $channel_history;
    }
    private function user_id_to_name( $userId ) {
        $user = $this->user_list[$userId];
        if ($user) {
            return $user['real_name'] ? $user['real_name'] : $user['name'];
        }
        else {
            return 'Unknown';
        }
    }
    
    private function coloncode_to_emoji( $coloncode ) {
        $emoji = $this->all_emojis[$coloncode];
        if ( $emoji ) {
            if ( substr( $emoji, 0, 8 ) == 'https://' ) {
                return '<img class="emoji" src="' . $emoji . '" title="' . $coloncode . '">';
            }
    
            if (substr($emoji, 0, 6) == 'alias:') {
                return $this->coloncode_to_emoji( substr( $coloncode, 6 ) );
            }
            
            return $emoji;
    
        }
    
        return ':' . $coloncode . ':'; 
    }
    
    private function replace_slack_tags( $text ) {
        $text = preg_replace_callback(
            '/<@([a-zA-Z0-9]+)>/',
            function ($matches) {
                return $this->user_id_to_name( $matches[1] );
            },
            $text
        );
        
        $text = preg_replace_callback(
            '/:([a-zA-Z0-9_\-]+)(::[a-zA-Z0-9_\-])?:/',
            function ( $matches ) {
                return $this->coloncode_to_emoji($matches[1]);
            },
            $text
        );
        
        $text = preg_replace_callback(
            '/<(https?:\/\/.+?)\\|([^>]+?)>/',
            function ( $matches ) {
                return ' <a target="_top" href="' . $matches['1'] . '" target="_blank">' . $matches[2] . '</a> ';
            },
            $text
        );
        
        $text = preg_replace_callback(
            '/<(https?:\/\/.+?)>/',
            function ( $matches ) {
                return ' <a target="_top" href="' . $matches['1'] . '" target="_blank">' . $matches[1] . '</a> ';
            },
            $text
        );
    
        $text = preg_replace(
            '/<#[a-zA-Z0-9]+\|([a-zA-Z0-9æøåÅÆØäöÄÖ\-_]+)>/',
            '#$1',
            $text
        );
    
        // 3+ are replaced with just two
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
    
        return $text;
    }
    
    private function render_reactions( $reactions ) {
        $html = '';
        foreach ($reactions as $r) {
            $emoji = $r['name'];
            $skin_modifier_pos = stripos( $emoji, '::' );
            if ( $skin_modifier_pos ) {
                $emoji = substr( $emoji, 0, $skin_modifier_pos );
            }
    
            $html .= '<span class="reaction"><i title="' . $emoji . '">' . $this->coloncode_to_emoji($emoji) . '</i> <small>' . $r['count'] . '</small>' . '</span>';
        }
    
        return $html;
    }
    
    private function render_avatar( $user ) {
        return '<img class="avatar" src="' . $user['profile']['image_48'] . '" aria-hidden="true" title="">';
    }
    
    private function render_userinfo( $message, $user ) {
        $html = '<strong class="username">' . $this->user_id_to_name( $user['id'] ) . '</strong> ';
    
        $html .= '<small class="timestamp">' . date( 'l, F jS \a\t g:i a', $message['ts'] ) . '</small>';
    
        return $html;
    }
    
    private function render_user_message( $message, $user ) {
        $html = '<div class="slack-message">';
        if ( isset( $message['parent_user_id'] ) ) {
            return '';
        }
    
        $html .= $this->render_avatar( $user );
    
        $html .= '<div class="content">';
    
        $html .= $this->render_userinfo( $message, $user );
        
        $html .= '<div class="message">' . $this->replace_slack_tags( $message['text'] ) . '</div>';
        
        if (isset($message['reactions'])) {
            $html .= $this->render_reactions( $message['reactions'] );
        }
    
        $html .= '</div>'; // .content
        $html .= '</div>'; // .slack-message
    
        return $html;
    }
    
    private function render_bot_message( $message, $username ) {
        $html = '<div class="slack-message">';
        if ( isset( $message['parent_user_id'] ) ) {
            return '';
        }
        $html .= '<img class="avatar" src="' . $message['icons']['image_64'] . '" aria-hidden="true" title="">';
        $html .= '<div class="content">';
        $html .= '<strong class="username">' . $username . '</strong> ';
        $html .= '<small class="timestamp">' . date( 'l, F jS \a\t g:i a', $message['ts'] ) . '</small>';
        $html .= '<div class="message">' . $this->replace_slack_tags( $message['text'])  . '</div>';
            
        if ( isset( $message['reactions'] ) ) {
            $html .= $this->render_reactions( $message['reactions'] );
        }
        $html .= '</div>'; // .content
        $html .= '</div>'; // .slack-message
        return $html;
    }
    
    private function render_file_message( $message, $user ) {
        var_dump($user);
        $file = $message['file'];
        $html = '<div class="slack-message">';
    
        $html .= $this->render_avatar( $user );
        
        $html .= '<div class="content file">';
        
        if ( $file['pretty_type'] === 'Post' ) {
            $html .= $this->render_userinfo( $message, $user );
            $html .= '<div class="document">';
            $html .= '<h2>' . $file['title'] . '</h2>';
            $html .= '<hr>';
            $html .= $file['preview'];
            $html .= '<a class="readmore" target="_top" href="' . $file['permalink_public'] . '">Kilkk her for å lese hele posten</a>';
            $html .= '</div>';
        }
        else {
            $html .= '<div class="message">' . $this->replace_slack_tags( $message['text'] ) . '</div>';        
        }
    
        $html .= $this->render_reactions( $file['reactions'] );
    
        $html .= '</div>'; // .content
        $html .= '</div>'; // .slack-message
        return $html;
    }
    
    /**
     * 
     */
    private function render_message( $message, $user_list ) {
        $html = '';
        switch ( $message['type'] ) {
            case 'message':
                if ( empty( $message['subtype'] ) ) {
                    return $this->render_user_message( $message, $this->user_list[$message['user']] );                
                }
    
                switch( $message['subtype'] ) {
    
                    case 'file_share':
                        return $this->render_file_message( $message, $this->user_list[$message['user']] );
            case 'bot_message':
                        return $this->render_bot_message( $message, $message['username'] );
                    case 'channel_join':
                    default:
                        return;
                }
                
            default:
                return;
        }
    }
}

?>