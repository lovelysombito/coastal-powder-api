import React, { useEffect, useState} from 'react';

const ProductAutocomplete = (props) => {

    const [options, setOptions] = useState(props.options || []);
    const [origOptions, setorigOptions] = useState(props.options || []);
    const [value, setValue] = useState(props.value || '');
    const [optionsOpened, setOptionsOpened] = useState(false);
    const activeIndex = options.findIndex(option => option.product_name.toLowerCase().indexOf(value.toLowerCase()) > -1);

    const [activeOptionIndex, setactiveOptionIndex] = useState(activeIndex > -1 ? activeIndex : 0);

    const handleOnFocus = e => {
        setOptionsOpened(true);
    }

    const handleOnKeyDown = e => {
        if(e.keyCode == 40 && activeOptionIndex != (options.length-1)) {
            setactiveOptionIndex(activeOptionIndex + 1);
        } else if(e.keyCode == 40 && !optionsOpened) {
            setOptionsOpened(true);
            setactiveOptionIndex(0);
        } else if(e.keyCode == 38 && activeOptionIndex != 0) {
            setactiveOptionIndex(activeOptionIndex - 1);
        } else if(e.keyCode == 13 || e.keyCode == 9) {

            if(!e.shiftKey) {
                setValue(options[activeOptionIndex].product_name)
                setOptionsOpened(false);
                if(props.onChange) {
                    props.onChange(options[activeOptionIndex].product_name);
                }
                if(props.onBlur) {
                    props.onBlur(e);
                }
            }

        }


    }

    const handleonBlur = (e) => {
        setOptionsOpened(false);
        if(props.onBlur) {
            props.onBlur(e);
        }
    }

    const handleOnChange = e => {
        setOptions(origOptions);
        setOptionsOpened(true);
        let val = e.target.value;
        setValue(val);

        if( val && val !== '') {
            val = e.target.value.toLowerCase()
            const filteredOptions = origOptions.filter(option => (option.product_name + ' ' + option.description).toLowerCase().indexOf(val) > -1);
            setOptions(filteredOptions);
            const activeIndex = filteredOptions.findIndex(option => (option.product_name + ' ' + option.description).toLowerCase().indexOf(val.toLowerCase()) > -1);
            setactiveOptionIndex(activeIndex > -1 ? activeIndex : 0);
        }

    }

    const handleOnClickOption = option => e => {
        setValue(option)
        setOptionsOpened(false);
        if(props.onChange) {
            props.onChange(option.product_name);
        }
    }

    return (
            <React.Fragment>
                <input autoComplete={'off'} {...props} onFocus={handleOnFocus} onKeyDown={handleOnKeyDown} onBlur={handleonBlur} onChange={handleOnChange} value={value}  className='form-control' type="text" />
                {
                    optionsOpened && (
                       <div className='d-block auto-complete'>
                            <ul className='list-group position-absolute'>
                                {
                                    options && options.map((option, i) => (
                                        <li key={i} onMouseDown={handleOnClickOption(option)} className={(activeOptionIndex == i && 'active') + ' list-group-item list-group-item-action'}>{option.product_name ? option.product_name + (option.description ? ' ' + option.description : '') : option}</li>
                                    ))
                                }
                            </ul>
                       </div>
                    )
                }
            </React.Fragment>
    );
    }


export default ProductAutocomplete;
