<?php


namespace ChatBot;


class ChB_Notifications {
	const SHORT_INTERVAL = 2 * 60 * 60;//2h

	public static function getNotificationsKVSPRefix() {
		return ChB_Settings()->salt . '_NTF_';
	}

	public static function registerQuestion( ChB_User $user, $is_image, $is_talk_to_human ) {
		$ntf_key = ChB_Notifications::getNotificationsKVSPRefix() . $user->fb_user_id;
		$ntf_val = ChB_Settings()->kvs->get( $ntf_key );

		$add           = true;
		$changed       = false;
		$notifications = null;
		$now           = time();
		if ( ! empty( $ntf_val ) ) {
			$notifications = json_decode( $ntf_val, true );
			foreach ( $notifications as &$notification ) {
				if ( ! empty( $notification['ans'] ) ) {
					continue;
				}

				//question already registered
				if ( $now - $notification['ts'] < 24 * 60 * 60 ) {
					if ( $is_image && empty( $notification['img'] ) ) {
						$notification['img'] = 1;
						$changed             = true;
					}
					if ( $is_talk_to_human && empty( $notification['tth'] ) ) {
						$notification['tth'] = 1;
						$changed             = true;
					}
					$add = false;
				} else {
					$notification['ans'] = self::NTF_TYPE_NO_ANSWER;
					$changed             = true;
				}
			}
			unset( $notification );
		}

		if ( $add ) {
			$now          = time();
			$notification = [ 'ts' => $now ];
			if ( $is_image ) {
				$notification['img'] = 1;
			}
			if ( $is_talk_to_human ) {
				$notification['tth'] = 1;
			}

			$notifications[] = $notification;
			$changed         = true;
		}

		if ( $changed ) {
			$ntf_val = json_encode( $notifications );
			ChB_Settings()->kvs->set( $ntf_key, $ntf_val );
			ChB_Common::my_log( 'registerQuestion save: ' . $ntf_key . '->' . $ntf_val );
		}
	}

	public static function registerAnswer( ChB_User $user ) {
		$user->unsetBlock4UserReminders();

		$ntf_key = ChB_Notifications::getNotificationsKVSPRefix() . $user->fb_user_id;
		$ntf_val = ChB_Settings()->kvs->get( $ntf_key );

		if ( empty( $ntf_val ) ) {
			return;
		}

		$changed       = false;
		$notifications = json_decode( $ntf_val, true );
		foreach ( $notifications as &$notification ) {
			if ( empty( $notification['ans'] ) ) {
				$notification['ans'] = time();
				$changed             = true;
				break;
			}
		}

		if ( $changed ) {
			$ntf_val = json_encode( $notifications );
			ChB_Settings()->kvs->set( $ntf_key, $ntf_val );
			ChB_Common::my_log( 'registerAnswer save: ' . $ntf_key . '->' . $ntf_val );
		}
	}

	public static function deleteNotification( $subscriber_id, $ts ) {
		$ntf_key = ChB_Notifications::getNotificationsKVSPRefix() . $subscriber_id;
		$ntf_val = ChB_Settings()->kvs->get( $ntf_key );
		if ( empty( $ntf_val ) ) {
			return false;
		}
		$changed       = false;
		$notifications = json_decode( $ntf_val, true );
		foreach ( $notifications as &$notification ) {
			if ( $notification['ts'] == $ts ) {
				$notification['ans'] = self::NTF_TYPE_DELETED;
				$changed             = true;
				break;
			}
		}
		if ( $changed ) {
			$ntf_val = json_encode( $notifications );
			ChB_Settings()->kvs->set( $ntf_key, $ntf_val );
			ChB_Common::my_log( 'deleteNotification save: ' . $ntf_key . '->' . $ntf_val );

			return true;
		}

		return false;
	}

	public static function getAllNotifications() {
		ChB_Settings()->tic( 'scan' );
		$notifications = ChB_Settings()->kvs->scanAllByPrefix( ChB_Notifications::getNotificationsKVSPRefix() );
		ChB_Settings()->toc( 'scan' );

		ChB_Settings()->tic( 'decode' );
		foreach ( $notifications as $ntf_key => $notif_val ) {
			$notifications[ $ntf_key ] = json_decode( $notif_val, true );
		}
		ChB_Settings()->toc( 'decode' );

		return $notifications;
	}

	public static function getUnansweredNotifications( $window = null, $notifications = null ) {
		if ( empty( $notifications ) ) {
			$notifications = self::getAllNotifications();
		}
		$res = [ 'lost' => [], 'to_answer' => [] ];
		$now = time();
		foreach ( $notifications as $ntf_key => $ntf_vals ) {
			$len      = count( $ntf_vals );
			$last_ntf = $ntf_vals[ $len - 1 ];
			if ( ! empty( $window ) && ( $now - $last_ntf['ts'] ) > $window ) {
				continue;
			}
			$sub_id    = str_replace( ChB_Notifications::getNotificationsKVSPRefix(), '', $ntf_key );
			$is_lost   = false;
			$to_answer = false;
			if ( empty( $last_ntf['ans'] ) ) {
				$qst_ts = $last_ntf['ts'];
				$not_ok = true;
				if ( $len > 1 ) {
					$prev_ans_ts = $ntf_vals[ $len - 2 ]['ans'];
					if ( is_numeric( $prev_ans_ts ) && ( ( $qst_ts - $prev_ans_ts ) < self::SHORT_INTERVAL ) ) {
						$not_ok = false;
					}
				}

				if ( $now - $qst_ts > 24 * 60 * 60 ) {
					$is_lost = $not_ok;
				} else {
					$to_answer = $not_ok;
				}
			}

			if ( $is_lost ) {
				$res['lost'][ $sub_id ] = $last_ntf;
			} elseif ( $to_answer ) {
				$res['to_answer'][ $sub_id ] = $last_ntf;
			}
		}

		return $res;
	}

	public static function getNotificationsCounters() {

		$window30d = 30 * 24 * 3600;
		$window48h = 48 * 3600;
		$window24h = 24 * 3600;
		$window21h = 21 * 3600;

		$ts_window30d_start = time() - $window30d;
		$ts_window48h_start = time() - $window48h;
		$ts_window24h_start = time() - $window24h;
		$ts_window21h_start = time() - $window21h;

		$notifications  = self::getAllNotifications();
		$unanswered_ntf = self::getUnansweredNotifications( $window30d, $notifications );

		$counters = [
			'expirein3h'  => [],
			'toanswer'    => [],
			'answered24h' => [ 'all' => 0, 'tth' => 0, 'img' => 0 ],
			'answered48h' => [ 'all' => 0, 'tth' => 0, 'img' => 0 ],
			'lost48h'     => [ 'all' => 0, 'tth' => 0, 'img' => 0 ],
			'answered30d' => [ 'all' => 0, 'tth' => 0, 'img' => 0 ],
			'lost30d'     => [ 'all' => 0, 'tth' => 0, 'img' => 0 ],
			'deleted30d'  => [ 'all' => 0, 'tth' => 0, 'img' => 0 ],
		];

		foreach ( $notifications as $ntf_key => $ntf_vals ) {
			$sub_id = str_replace( ChB_Notifications::getNotificationsKVSPRefix(), '', $ntf_key );

			foreach ( $ntf_vals as $ntf_val ) {
				if ( $ntf_val['ts'] < $ts_window30d_start ) {
					continue;
				}

				$ntf_desc = self::describeNotification( $ntf_val, $unanswered_ntf, $sub_id );

				if ( $ntf_desc['type'] === self::NTF_TYPE_TO_ANSWER ) {
					if ( $ntf_val['ts'] <= $ts_window21h_start ) {
						$counters['expirein3h'][] = $sub_id;
					} else {
						$counters['toanswer'][] = $sub_id;
					}
				} else {

					$inds = [];

					if ( $ntf_desc['type'] === self::NTF_TYPE_DELETED ) {
						$inds[] = 'deleted30d';
					} else {
						$ind = '';
						if ( $ntf_desc['type'] == self::NTF_TYPE_ANSWERED ) {
							$ind = 'answered';
						} elseif ( $ntf_desc['type'] == self::NTF_TYPE_LOST || $ntf_desc['type'] == self::NTF_TYPE_NO_ANSWER ) {
							$ind = 'lost';
						}

						if ( ! empty( $ind ) ) {
							$inds[] = $ind . '30d';
							if ( $ntf_val['ts'] > $ts_window24h_start ) {
								$inds[] = $ind . '24h';
							}
							if ( $ntf_val['ts'] > $ts_window48h_start ) {
								$inds[] = $ind . '48h';
							}
						}
					}

					foreach ( $inds as $ind ) {
						$counters[ $ind ]['all'] ++;
						if ( ! empty( $ntf_desc['img'] ) ) {
							$counters[ $ind ]['img'] ++;
						}
						if ( ! empty( $ntf_desc['tth'] ) ) {
							$counters[ $ind ]['tth'] ++;
						}
					}
				}
			}
		}

		return $counters;
	}

	const NTF_TYPE_LOST = 'LOST';
	const NTF_TYPE_TO_ANSWER = 'TO ANSWER';
	const NTF_TYPE_NO_ANSWER = 'no';
	const NTF_TYPE_NO_ANSWER_REQUIRED = 'not req';
	const NTF_TYPE_ANSWERED = 'answered';
	const NTF_TYPE_DELETED = 'del';

	public static function describeNotification( $ntf_val, &$unanswered_ntf, $sub_id ) {

		$res['qst']   = ChB_Common::timestamp2DateTime( $ntf_val['ts'] );
		$res['delay'] = '';

		if ( empty( $ntf_val['ans'] ) ) {
			if ( ! empty( $unanswered_ntf['lost'][ $sub_id ] ) ) {
				$res['text'] = $res['type'] = self::NTF_TYPE_LOST;
			} elseif ( ! empty( $unanswered_ntf['to_answer'][ $sub_id ] ) ) {
				$res['text'] = $res['type'] = self::NTF_TYPE_TO_ANSWER;
			} else {
				$res['text'] = $res['type'] = self::NTF_TYPE_NO_ANSWER_REQUIRED;
			}
		} elseif ( is_numeric( $ntf_val['ans'] ) ) {
			$res['type']  = self::NTF_TYPE_ANSWERED;
			$res['text']  = ChB_Common::timestamp2DateTime( $ntf_val['ans'] );
			$res['delay'] = number_format( ( $ntf_val['ans'] - $ntf_val['ts'] ) / 3600, 1 );
		} else {
			$res['text'] = $res['type'] = $ntf_val['ans'];
		}

		$res['img'] = ( empty( $ntf_val['img'] ) ? '' : 'img' );
		$res['tth'] = ( empty( $ntf_val['tth'] ) ? '' : 'tth' );

		return $res;
	}

	public static function checkNotifications() {
		$counters = self::getNotificationsCounters();
		if ( empty( $counters['expirein3h'] ) ) {
			return;
		}

		$body = self::printNotificationSummary( $counters );
		if ( WC()->mailer()->get_from_address() ) {
			$headers = [
				'From:' . ( WC()->mailer()->get_from_name() ? '"' . WC()->mailer()->get_from_name() . '"' : '' ) . '<' . WC()->mailer()->get_from_address() . '>',
				'Content-Type: text/html; charset=UTF-8'
			];
		} else {
			$headers = [
				'From:"Bot"<do-not-reply@' . ChB_Settings()->getDomainPath() . '>',
				'Content-Type: text/html; charset=UTF-8'
			];
		}

		$subj = '[' . ChB_Settings()->getParam( 'fb_page_username' ) . '] Unanswered messages. 24h window is expiring!';
		foreach ( ChB_Settings()->getParam( 'managers2email_on_ntf' ) as $email ) {
			wp_mail( $email, $subj, $body, $headers );
		}
	}

	public static function printNotificationSummary( $counters = null ) {

		if ( $counters == null ) {
			$counters = self::getNotificationsCounters();
		}

		$html = '';
		$html .= '<b>EXPIRE IN 3H (' . esc_html( count( $counters['expirein3h'] ) ) . ')</b>';
		if ( empty( $counters['expirein3h'] ) ) {
			$html .= '<br>--';
		} else {
			foreach ( $counters['expirein3h'] as $sub_id ) {
				$html .= '<br>' . ChB_ManyChat::getMCLiveChatLinkHTML( $sub_id ) . ' ' . esc_html( ChB_User::getSubscriberDisplayName( $sub_id, false ) );
			}
		}

		$html .= '<br><br>';

		$html .= '<b>TO ANSWER (' . esc_html( count( $counters['toanswer'] ) ) . ')</b>';
		if ( empty( $counters['toanswer'] ) ) {
			$html .= '<br>--';
		} else {
			foreach ( $counters['toanswer'] as $sub_id ) {
				$html .= '<br>' . ChB_ManyChat::getMCLiveChatLinkHTML( $sub_id ) . ' ' . esc_html( ChB_User::getSubscriberDisplayName( $sub_id, false ) );
			}
		}

		$html .= '<br><br>';
		$html .= '<table %s1>';
		$st   = ' style="border: 1px solid #000000;" ';
		$html .= '<tr %s1><td %s1>TYPE</td>' . '<td %s1>ALL</td><td %s1>TTH</td><td %s1>IMG</td></tr>';

		foreach ( $counters as $key => $counter ) {
			if ( $key != 'expirein3h' && $key != 'toanswer' ) {
				$html .= '<tr %s1><td %s1>' . esc_html( $key ) . '</td><td %s1>' . esc_html( $counter['all'] ) . '</td><td %s1>' . esc_html( $counter['tth'] ) . '</td><td %s1>' . esc_html( $counter['img'] ) . '</td></tr>';
			}
		}

		$html .= '</table>';

		if ( ChB_Common::utilIsDefined() ) {
			$html .= '<br><br><a target="_blank" href="' . esc_url( get_wy_util_plugin_dir_url() . '?task=ntf_summary' ) . '">>> refresh this report in new window</a>';
			$html .= '<br><a target="_blank" href="' . esc_url( get_wy_util_plugin_dir_url() . '?task=notifications&days=30' ) . '">>> open details for 30 days</a>';
		}

		$html = str_replace( '%s1', $st, $html );

		return $html;
	}


}