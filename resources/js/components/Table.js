import { useTable } from "react-table";
import React, { useEffect, useState} from 'react';
import { TextField, Checkbox } from '@mui/material';
import { createFilterOptions } from '@mui/material/Autocomplete';
import { materials, coatingLine, aluminiumTreatments, steelTreatments } from "../constants";
import Autocomplete from "./Autocomplete";
import ProductAutocomplete from "./ProductAutocomplete";
import { useSortable } from "@dnd-kit/sortable";

import {
    closestCenter,
    DndContext,
    DragOverlay,
    KeyboardSensor,
    MouseSensor,
    TouchSensor,
    useSensor,
    useSensors
  } from "@dnd-kit/core";
  import { restrictToVerticalAxis } from "@dnd-kit/modifiers";
  import {
    arrayMove,
    SortableContext,
    verticalListSortingStrategy
  } from "@dnd-kit/sortable";
import { CSS } from "@dnd-kit/utilities";
import styled from "styled-components";
import { useMemo } from "react";

const Table = (props) => {
    const [items, setItems] = useState([]);
    const [products, setProducts] = useState([]);
    const [hubspotProducts, setHubspotProducts] = useState([]);
    const [colourList, setColourList] = useState([]);
    const [key, setKey] = useState('');


    useEffect(() => {
        setItems(props.items);
    }, [props.items]);

    useEffect(() => {
        setHubspotProducts(props.products);
        const products  = props.products.map(product => { return {product_name:product.product_name, description: product.description } } );
        setProducts(products);
    }, [props.products]);

    useEffect(() => {
        setColourList(props.colourList);
    }, [props.colourList]);

    const filterOptions = createFilterOptions({
        matchFrom: 'any',
        stringify: (option) => option.product_name + " " + option.description + " " + option.sku,
        limit: 50,
    });


    const EditableCell = ({
        value: initialValue,
        row: { index, original },
        column: { id },
      }, columnIndex, type,  handleOnKeyDown = () => {}) => {
        // We need to keep and update the state of the cell normally
        const [value, setValue] = useState(initialValue)
        const onChange = e => {
            if (type === 'number') {
                if (!isNaN(e.target.value) && e.target.value.split('.').length > 1) {
                    if (e.target.value.split('.')[1].length > 2) {
                        e.target.value = parseFloat(e.target.value).toFixed(3);
                    }
                }

            }

            setValue(e.target.value)
        }

        // We'll only update the external data when the input is blurred
        const onBlur = (e) => {
            e.preventDefault();
            props.updateItem(index, {[id]: value}, id);

            if(e.relatedTarget) {
                setKey(e.relatedTarget.id);
            } else {
                setKey('');
            }
        }

        return <input
            onChange={onChange}
            onBlur={onBlur}
            onFocus={(e) => {e.target.select()}}
            id={id + '-' + index}
            disabled={original.add_product || props.isSaving || props.isLoading || props.isSearching}
            value={original.add_product ? '' : ( type === 'number' ? (!isNaN(value) ? parseFloat(value) : value) : value)}
            className="form-control"
            tabIndex={`${(index * 9) + columnIndex}`}
            autoFocus={(id + '-' + index) == key}
            type={type}
            autoComplete={"off"}
            onKeyDown={handleOnKeyDown(value)} 
        />
    }

    const EditableDropdownAutoCompleteCell = ({
        value: initialValue,
        row: { index, original },
        column,
      }, options, columnIndex) => {

        // We need to keep and update the state of the cell normally
        const [value, setValue] = useState(initialValue)
        const [showDropdown, setshowDropdown] = useState(false)

        const onChange = (value) => {
            setValue(value)
            setKey(column.id + '-' + index);
            const hubspotProductIndex = hubspotProducts.findIndex(product => product.product_name.toLowerCase() == value.toLowerCase());

            if(hubspotProductIndex > -1) {
                const product = hubspotProducts[hubspotProductIndex];
                const data = {
                    product: value,
                    hs_product_id: product.product_id,
                    product_id: product.product_id,
                    description: product.description,
                    price: product.price,
                    edit_product: false,
                    add_product: false
                }
                props.updateItem(index,data,'price');
                props.setIsAdding(false);
                setKey(`description-${index}`);
            }

        }

        const onBlur = (e) => {
            e.preventDefault();
            let rowIndex = e.target.id.split('-')[1];

            if(e.relatedTarget) {
                setKey(e.relatedTarget.id);
            } else {
                setKey('');
            }

            if(e.keyCode == 13 || (e.keyCode == 9 && !e.shiftKey)) {
                if(e.target.id.includes('product')) {
                    setKey(`description-${rowIndex}`);
                }
            }
        }


        const handleProductNameClick = e => {
            setshowDropdown(!showDropdown);
        }

        const handleEditClick = rowIndex => e => {
           props.editRow(rowIndex);
        }

        const handleDeleteClick = rowIndex => e => {
            props.deleteItem(rowIndex);
        }

        if (original.add_product || original.edit_product) {
            return <div>
                <ProductAutocomplete
                    id={column.id + '-' + index}
                    options={options}
                    tabIndex={`${(index * 9) + columnIndex}`}
                    label=""
                    getOptionLabel={(option) => option.product_name + " " + option.description}
                    filterOptions={filterOptions}
                    style={{ width: 300 }}
                    onChange={onChange}
                    onBlur={onBlur}
                    value={value}
                    autoFocus={(column.id + '-' + index) == key}
                />
            </div>
        }

        return (
            <React.Fragment>
                <button
                    id={column.id + '-' + index}
                    className="btn btn-link dropdown-toggle line-item"
                    data-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                    disabled={props.isSaving || props.isLoading || props.isSearching}
                    tabIndex={`${(index * 9) + columnIndex}`}
                    autoFocus={(column.id + '-' + index) == key}
                    onBlur={onBlur}
                    onClick={handleProductNameClick}
                    // onKeyDown={handleOnkeyDown}
                >
                    {value}
                </button>
                {
                    showDropdown && (
                        <ul className={`dropdown-menu ${(showDropdown && 'show')}`}>
                            <li><button onClick={handleEditClick(index)} className="dropdown-item" >Edit</button></li>
                            <li><button onClick={handleDeleteClick(index)} className="dropdown-item" >Delete</button></li>
                        </ul>
                    )
                }
            </React.Fragment>
        )

    }


    const EditableAutoCompleteCell = ({
        value: initialValue,
        row: { index, original },
        column,
      }, options, columnIndex)  => {

        const [value, setValue] = useState(initialValue)

        const onChange = value => {
            props.updateItem(index, {[column.id]: value}, column.id);
            setValue(value);
            setKey(column.id + '-' + index);
        }
        // We'll only update the external data when the input is blurred
        const onBlur = (e) => {
            e.preventDefault();
            let rowIndex = e.target.id.split('-')[1];

            if(e.relatedTarget) {
                setKey(e.relatedTarget.id);
            } else {
                setKey('')
            }


            if(e.keyCode == 13 || (e.keyCode == 9 && !e.shiftKey)) {

                if(e.target.id.includes('colour')) {
                    setKey(`material-${rowIndex}`);
                }
                if(e.target.id.includes('material')) {
                    setKey(`treatment-${rowIndex}`);
                }
                if(e.target.id.includes('treatment')) {
                    setKey(`coating_line-${rowIndex}`);
                }

                //last column
                if(e.target.id.includes('coating_line')) {
                    if(rowIndex == (items.length-1)) {
                       props.addnewRow();
                    }
                    //adds 1 to rowIndex to next row
                    rowIndex++;
                    setKey(`product-${rowIndex}`);

                }

                return 0
            }

            return 0

        }
    

        return <Autocomplete
            options={options}
            sx={{ width: 180 }}
            renderInput={(params) => <TextField {...params} label="Colour" variant="outlined" />}
            onChange={onChange}
            value={original.add_product ? null : value}
            id={column.id + '-' + index}
            autoFocus={(column.id + '-' + index) == key}
            tabIndex={`${(index * 9) + columnIndex}`}
            onBlur={onBlur}
            style={{ textTransform: 'capitalize' }}
        />
    }

    const columns = React.useMemo( () => [
        { // Bulk select
            id: 'select',
            width: 80,
            Cell: ({ row }) => {
                const handleChange = (event) => {
                    props.updateItem(row.index, {checked: !row.original.checked}, 'checked');
                };
                return (
                    <Checkbox
                        checked={row.original.checked}
                        onChange={handleChange}
                        inputProps={{ 'aria-label': 'controlled' }}
                    />
                );
            },
        },
        { // Product
            Header: 'Product',
            accessor: 'product', // accessor is the "key" in the data
            minWidth: 100,
            Cell: (cell) => {
                return EditableDropdownAutoCompleteCell(cell, products, 0)
            }
        },
        { // Description
            Header: 'Description',
            accessor: 'description',
            minWidth: 600,
            Cell: (cell) => {
                return EditableCell(cell, 1, 'text')
            }
        },
        { // Unit of Measurement
            Header: 'Unit of Measure',
            accessor: 'unit_of_measurement',
            Cell: (cell) => {
                return EditableCell(cell, 2, 'text')
            }
        },
        { // Quantity
            Header: 'Quantity',
            accessor: 'quantity',
            Cell: (cell) => {
                return EditableCell(cell, 3)
            }
        },
        { // Unit Price
            Header: 'Unit Price',
            accessor: 'price',
            Cell: (cell) => {
                const handleSetKey = () => setKey(`product-${cell.rows.length}`)
                return EditTablePriceCell(cell, 4, 'number', handleSetKey)
            }
        },
        { // Total Price
            Header: 'Total Price',
            accessor: 'total_price',
            Cell: (cell) => {
                return <input
                    disabled
                    readOnly
                    value={cell.row.original.add_product ? 0 : parseFloat(cell.row.original.price * cell.row.original.quantity) ? parseFloat(cell.row.original.price * cell.row.original.quantity).toFixed(2) : 0}
                    className="form-control"
                />
            }
        },
        { // Colour
            Header: 'Colour',
            sortable: false,
            accessor: 'colour',
            Cell: (cell) => {
                return EditableAutoCompleteCell(cell, colourList, 5)
            }
        },
        { // Material
            Header: 'Material',
            sortable: false,
            accessor: 'material',
            Cell: (cell) => {
                return EditableAutoCompleteCell(cell, materials, 6)
            }
        },
        { // Treatment
            Header: 'Treatment',
            sortable: false,
            accessor: 'treatment',
            Cell: (cell) => {
                const treatmentOptions = cell.row.original.material ? ( cell.row.original.material === "Steel" ? steelTreatments : aluminiumTreatments ) : [];
                return EditableAutoCompleteCell(cell, treatmentOptions, 7)
            }
        },
        { // Coating Line
            Header: 'Powder Coating Line',
            sortable: false,
            accessor: 'coating_line',
            Cell: (cell) => {
                return EditableAutoCompleteCell(cell, coatingLine, 8)
            }
        },
    ]);


    const EditTablePriceCell = ({
        value: initialValue,
        row: { index, original },
        column: { id },
      }, columnIndex, type, handleSetKey) => {
        // We need to keep and update the state of the cell normally
        const [value, setValue] = useState(initialValue)
        const onChange = e => {
            if (type === 'number') {
                if (!isNaN(e.target.value) && e.target.value.split('.').length > 1) {
                    if (e.target.value.split('.')[1].length > 2) {
                        e.target.value = parseFloat(e.target.value).toFixed(3);
                    }
                }

            }

            setValue(e.target.value)
        }

        // We'll only update the external data when the input is blurred
        const onBlur = (e) => {
            e.preventDefault();
            props.updateItem(index, {[id]: value}, id);

            if(e.relatedTarget) {
                setKey(e.relatedTarget.id);
            } else {
                setKey('');
            }
        }

        const handleOnKeyDown = (e) => {
            if (e.keyCode === 13 && e.target.id.includes('price'))  {
                props.addnewRow();
                props.updateItem(index, {[id]: value}, id);
                handleSetKey()
            }
        }

        return <input
            onChange={onChange}
            onBlur={onBlur}
            onFocus={(e) => {e.target.select()}}
            id={id + '-' + index}
            disabled={original.add_product || props.isSaving || props.isLoading || props.isSearching}
            value={original.add_product ? '' : ( type === 'number' ? (!isNaN(value) ? parseFloat(value) : value) : value)}
            className="form-control"
            tabIndex={`${(index * 9) + columnIndex}`}
            autoFocus={(id + '-' + index) == key}
            type={type}
            autoComplete={"off"}
            onKeyDown={handleOnKeyDown} 
        />
    }


    const LineItems = (props) => {
        const [activeId, setActiveId] = useState();


        const columns = props.columns;
        const data = props.data;

        const items = useMemo(() => data?.map(({ line_item_id }) => line_item_id), [data]);

        const {
            getTableProps,
            getTableBodyProps,
            headerGroups,
            rows,
            prepareRow,
        } = useTable({ columns, data });

        const sensors = useSensors(
            useSensor(MouseSensor, {}),
            useSensor(TouchSensor, {}),
            useSensor(KeyboardSensor, {})
          );

        function handleDragStart(event) {
            setActiveId(event.active.id);
        }

        function handleDragEnd(event) {
            const { active, over } = event;
            if (active.id !== over.id) {
                props.setLineItems((data) => {


                const oldIndex = items.indexOf(active.id);
                const newIndex = items.indexOf(over.id);


                const activeItemIndex = data.findIndex(item => item.line_item_id == active.id);
                if(activeItemIndex > -1) {
                    const activeItem = data[activeItemIndex];
                    activeItem.hs_position_on_quote = newIndex;
                }

                const overItemIndex = data.findIndex(item => item.line_item_id == over.id);

                if(overItemIndex > -1) {
                    const overItem = data[overItemIndex];
                    overItem.hs_position_on_quote = oldIndex;
                }

                const newArray= arrayMove(data, oldIndex, newIndex);
                return newArray;
                });
            }

            setActiveId(null);
        }

        function handleDragCancel() {
            setActiveId(null);
        }

        const selectedRow = React.useMemo(() => {
            if (!activeId) {
              return null;
            }
            const row = rows.find(({ original }) => original.line_item_id === activeId);
            prepareRow(row);
            return row;
        }, [activeId, rows, prepareRow]);

        const DraggableTableRow = ({ row }) => {
            const {
              attributes,
              listeners,
              transform,
              transition,
              setNodeRef,
              isDragging
            } = useSortable({
              id: row.original.line_item_id
            });
            const style = {
              transform: CSS.Transform.toString(transform),
              transition: transition
            };

            return (

              <tr ref={setNodeRef} style={style} {...row.getRowProps()} >

                {isDragging ? (
                  <td colSpan={row.cells.length}>&nbsp;</td>
                ) : (
                  row.cells.map((cell, i) => {
                    if (i === 0) {
                      return (

                        <td key={i} style={{minWidth: 200}} {...cell.getCellProps()}>
                            <DragHandle {...attributes} {...listeners} />
                            {cell.render("Cell")}
                        </td>
                      );
                    }
                    const width = i == 2 ? 350 :  ( (i === 3 || i === 4 || i === 5 || i === 8) ? 100 : ((i === 7 || i == 9) ? 125 : 150)  );
                    return (
                      <td style={{minWidth: width}} key={i} {...cell.getCellProps()}>
                        {cell.render("Cell")}
                      </td>
                    );
                  })
                )}
              </tr>
            );
        };

        const DragHandle = (props) => {
            const HandleWrapper = styled.div`
                height: 1rem;
                // vertical-align: bottom;
                display: inline-block;
                margin-right: 0.5rem;
                svg {
                    width: 100%;
                    height: 100%;
                }
                cursor: ${({ isDragging }) => (isDragging ? "grabbing" : "grab")};
            `;

            return (
                <HandleWrapper {...props}>
                  <svg
                    aria-hidden="true"
                    focusable="false"
                    data-prefix="fas"
                    data-icon="grip-vertical"
                    role="img"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 320 512"
                  >
                    <path
                      fill="currentColor"
                      d="M96 32H32C14.33 32 0 46.33 0 64v64c0 17.67 14.33 32 32 32h64c17.67 0 32-14.33 32-32V64c0-17.67-14.33-32-32-32zm0 160H32c-17.67 0-32 14.33-32 32v64c0 17.67 14.33 32 32 32h64c17.67 0 32-14.33 32-32v-64c0-17.67-14.33-32-32-32zm0 160H32c-17.67 0-32 14.33-32 32v64c0 17.67 14.33 32 32 32h64c17.67 0 32-14.33 32-32v-64c0-17.67-14.33-32-32-32zM288 32h-64c-17.67 0-32 14.33-32 32v64c0 17.67 14.33 32 32 32h64c17.67 0 32-14.33 32-32V64c0-17.67-14.33-32-32-32zm0 160h-64c-17.67 0-32 14.33-32 32v64c0 17.67 14.33 32 32 32h64c17.67 0 32-14.33 32-32v-64c0-17.67-14.33-32-32-32zm0 160h-64c-17.67 0-32 14.33-32 32v64c0 17.67 14.33 32 32 32h64c17.67 0 32-14.33 32-32v-64c0-17.67-14.33-32-32-32z"
                    ></path>
                  </svg>
                </HandleWrapper>
              );
        }


        return (
            <DndContext
                sensors={sensors}
                onDragEnd={handleDragEnd}
                onDragStart={handleDragStart}
                onDragCancel={handleDragCancel}
                collisionDetection={closestCenter}
                modifiers={[restrictToVerticalAxis]}
            >
                <table className="table" {...getTableProps()}>
                    <thead>
                        {headerGroups.map(headerGroup => (
                        <tr {...headerGroup.getHeaderGroupProps()}>
                            {headerGroup.headers.map(column => (
                            <th {...column.getHeaderProps()}>{column.render('Header')}</th>
                            ))}
                        </tr>
                        ))}
                    </thead>
                    <tbody {...getTableBodyProps()}>
                        <SortableContext items={items} strategy={verticalListSortingStrategy}>
                            {
                                rows.map((row, i) => {
                                    prepareRow(row)
                                    return (
                                        <DraggableTableRow key={row.original.line_item_id} row={row} />
                                    )
                                })
                            }
                        </SortableContext>
                    </tbody>
                </table>

                <DragOverlay>
                    {activeId && (
                    <table className="table" style={{ width: "100%" }}>
                        <tbody>
                        <tr {...selectedRow.getRowProps()} style={{ backgroundColor: "#ffffff"}}>
                            {selectedRow.cells.map((cell, i) => {
                                if (i === 0) {
                                    return (
                                        <td style={{
                                            minWidth: 200,
                                            boxShadow: "rgb(0 0 0 / 10%) 0px 20px 25px -5px, rgb(0 0 0 / 30%) 0px 10px 10px -5px",
                                        //    outline: "#3e1eb3 solid 1px"
                                        }} {...cell.getCellProps()}>
                                            <DragHandle isDragging />
                                            {cell.render("Cell")}
                                        </td>
                                    );
                                }
                                return (
                                    <td style={{
                                        boxShadow: "rgb(0 0 0 / 10%) 0px 20px 25px -5px, rgb(0 0 0 / 30%) 0px 10px 10px -5px",
                                        // outline: "#3e1eb3 solid 1px"
                                    }} {...cell.getCellProps()}>
                                        {cell.render("Cell")}
                                    </td>
                                );
                            })}
                        </tr>
                        </tbody>
                    </table>
                    )}
                </DragOverlay>
            </DndContext>

        )


    }

    return (
        <LineItems columns={columns} data={items} setLineItems={props.setLineItems} />
    );
}

export default Table;
