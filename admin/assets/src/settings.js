const { getSetting: getWcSetting } = wc.wcSettings;

const automatewoo = getWcSetting( 'automatewoo', {} );

const getSetting = ( property, fallback = false, settingsObject ) => {
	if (
		typeof settingsObject === 'object' &&
		settingsObject.hasOwnProperty( property )
	) {
		return settingsObject[ property ];
	}
	return fallback;
};

export const MANUAL_WORKFLOWS_BATCH_SIZE = getSetting(
	'batchSize',
	10,
	automatewoo.manualRunner
);
export const MANUAL_WORKFLOWS_HIGH_VOLUME_THRESHOLD = getSetting(
	'highVolumeThreshold',
	500,
	automatewoo.manualRunner
);
export const ADMIN_URL = getWcSetting( 'adminUrl', '' );
export const TRACKS_PREFIX = 'aw_';
