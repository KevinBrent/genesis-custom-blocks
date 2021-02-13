/**
 * External dependencies
 */
import * as React from 'react';
import className from 'classnames';

/**
 * WordPress dependencies
 */
import { TextControl } from '@wordpress/components';
import { useState } from '@wordpress/element';

const GcbURLControl = ( props ) => {
	const { field, getValue, onChange } = props;
	const initialValue = getValue( props );
	const value = 'undefined' !== typeof initialValue ? initialValue : field.default;
	const [ isError, setIsError ] = useState( false );

	return (
		<TextControl
			className={ className( {
				'text-control__error': isError,
			} ) }
			type="url"
			label={ field.label }
			placeholder={ field.placeholder || '' }
			help={ field.help }
			value={ value }
			onChange={ onChange }
			onFocus={ ( event ) => {
				setIsError( ! event.target.reportValidity() );
			} }
			onBlur={ ( event ) => {
				setIsError( ! event.target.checkValidity() );
			} }
		/>
	);
};

export default GcbURLControl;
