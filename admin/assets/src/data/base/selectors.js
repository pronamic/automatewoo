export const isRequesting = ( state, selector ) => {
	return state.requesting[ selector ] || false;
};

export const getError = ( state, selector ) => {
	return state.errors[ selector ] || false;
};
