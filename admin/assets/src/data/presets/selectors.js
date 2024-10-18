export * from '../base/selectors';

export const getPresets = ( state ) => {
	return state.presets;
};

export const didCreateWorkflow = ( state ) => {
	return state.didCreateWorkflow;
};
