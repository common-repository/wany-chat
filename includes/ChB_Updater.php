<?php


namespace ChatBot;


abstract class ChB_Updater {
	protected string $plugin_file;
	protected string $plugin_version;
	protected string $basename;
	protected string $plugin_id;

	abstract public static function instance();

	protected function __construct() {
		$this->basename             = plugin_basename( $this->plugin_file );
		$this->plugin_version       = $this->getPluginVersionFromCode();
	}

	public function checkPluginNewVersionWP() {
		$update_plugins = get_site_transient( 'update_plugins' );

		return ( empty( $update_plugins->response[ $this->basename ]->new_version ) ? null : $update_plugins->response[ $this->basename ]->new_version );
	}

	public function checkScheduleUpdatePluginVersionInDB() {
		$version_db   = $this->getPluginVersionFromDB( true );
		$version_code = $this->getPluginVersionFromCode();
		if ( version_compare( $version_db['plugin_version'], $version_code, '<' ) ) {
			if ( ! $version_db['db_update_in_progress'] ) {
				$this->scheduleUpdatePlugin();
			} else {
				ChB_Common::my_log( 'checkScheduleUpdatePluginVersionInDB: update is already running' );
			}
		}
	}

	public function updatePluginVersionInDB( $plugin_version, $db_update_in_progress ) {
		$options                          = get_option( $this->plugin_id . '_update' );
		$options['plugin_version']        = $plugin_version;
		$options['db_update_in_progress'] = $db_update_in_progress;
		update_option( $this->plugin_id . '_update', $options );
	}

	protected function scheduleUpdatePlugin() {
		// Scheduling database update
		if ( ! ChB_Events::eventIsScheduled( ChB_Events::CHB_EVENT_UPDATE_PLUGIN, [ 'plugin_id' => $this->plugin_id ] ) ) {
			ChB_Events::scheduleSingleEvent( ChB_Events::CHB_EVENT_UPDATE_PLUGIN, [ 'plugin_id' => $this->plugin_id ], 30 );
			ChB_Common::my_log( 'checkScheduleUpdatePluginVersionInDB scheduling update' );
		} else {
			ChB_Common::my_log( 'checkScheduleUpdatePluginVersionInDB: update is already scheduled' );
		}
	}

	public function getPluginVersionFromCode() {
		$plugin_data = get_file_data( $this->plugin_file, [ 'Version' => 'Version' ], 'plugin' );

		return $plugin_data['Version'];
	}

	public function getPluginVersionFromDB( $extended = false ) {
		$options = get_option( $this->plugin_id . '_update' );
		if ( $extended ) {
			return
				[
					'plugin_version'        => empty( $options['plugin_version'] ) ? null : $options['plugin_version'],
					'db_update_in_progress' => empty( $options['db_update_in_progress'] ) ? false : $options['db_update_in_progress']
				];
		} else {
			return empty( $options['plugin_version'] ) ? null : $options['plugin_version'];
		}
	}

	public function updatePlugin() {
		$prev_version = $this->getPluginVersionFromDB();
		$new_version  = $this->plugin_version;

		ChB_Common::my_log( $this->plugin_file . ' BEGIN updatePlugin prev_version=' . $prev_version . ' new_version=' . $new_version );
		$this->updatePluginVersionInDB( $prev_version, true );
		$updated_to_version = $this->launchUpdateActions( $prev_version, $new_version );
		if ( $updated_to_version ) {
			$this->updatePluginVersionInDB( $updated_to_version, false );
			ChB_Common::my_log( $this->plugin_file . ' END updatePlugin prev_version=' . $prev_version . ' new_version=' . $updated_to_version );
		} else {
			$this->updatePluginVersionInDB( $prev_version, false );
			ChB_Common::my_log( $this->plugin_file . ' END updatePlugin prev_version=' . $prev_version . '. NO NEW VERSION!!!' );
		}

		return true;
	}

	protected function launchUpdateActions( $prev_version, $new_version ) {
		if ( ! $prev_version ) {
			return $new_version;
		}// nothing to do

		ChB_Common::my_log( 'ChB_Updater: launching update actions for ' . $this->plugin_id );

		$res                            = true;
		$last_successful_version_action = null;
		foreach ( $this->getUpdateActions() as $version_action ) {
			if ( version_compare( $prev_version, $version_action, '<' )
			     && version_compare( $new_version, $version_action, '>=' ) ) {

				try {

					$updateActionName = 'updateActionV' . str_replace( '.', '_', $version_action );
					ChB_Common::my_log( 'launching ' . $updateActionName );

					//launching update action
					$res = $this->{$updateActionName}();

				} catch ( \Throwable $e ) {
					ChB_Common::my_log( 'EXCEPTION in ChB_Updater::launchUpdateActions() ' . $e->getMessage() . ' ' . $e->getTraceAsString() );
					$res = false;
				}

				if ( ! $res ) {
					ChB_Common::my_log( 'Cannot update plugin properly. Update action ' . $version_action . ' FAILED!!' );
					break;
				}

				$last_successful_version_action = $version_action;
			}
		}

		if ( ! $res ) {
			if ( $last_successful_version_action ) {
				ChB_Common::my_log( 'Last successful version update is ' . $last_successful_version_action );
			}

			return $last_successful_version_action;
		}

		return $new_version;
	}

	abstract protected function getUpdateActions();

}