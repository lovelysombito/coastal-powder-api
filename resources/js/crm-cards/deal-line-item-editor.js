import { createRoot } from 'react-dom/client';
import React, { useState, useEffect } from 'react'
import { getColours, getDealLineItems, getProducts, saveChanges, aluminiumTreatments, steelTreatments, currencyFormat, coatingLine, materials } from '../constants';
import Table from '../components/Table';
import BulkEdit from '../components/BulkEdit';
import { Autocomplete, TextField } from '@mui/material';
import Swal from 'sweetalert2';


const DealLineItemEditor = () => {

    const urlParams = new URLSearchParams(window.location.search);
    const dealId = urlParams.get('dealId');
    const userId = urlParams.get('userId');
    const signature = urlParams.get('signature');


    const [lineItems, setLineItems] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [isAdding, setIsAdding] = useState(false);
    const [isSaving, setIsSaving] = useState(false);
    const [isSyncing, setIsSyncing] = useState(false);
    const [products, setProducts] = useState([]);
    const [showToast, setShowToast] = useState(false);
    const [toastMessage, setToastMessage] = useState('');
    const [toastType, setToastType] = useState('');
    const [colours, setColours] = useState([]);
    const [colourList, setColourList] = useState([]);
    const [defaultColour, setDefaultColour] = useState('');
    const [defaultMaterial, setDefaultMaterial] = useState('');
    const [defaultTreatment, setDefaultTreatment] = useState('');
    const [defaultCoatingLine, setDefaultCoatingLine] = useState('');
    const [totalCost, setTotalCost] = useState(0);
    const [totalItems, setTotalItems] = useState(0);
    const [curentTempNumber, setCurentTempNumber] = useState(1);

    const [isBulkEdit, setIsBulkEdit] = useState(false);

    const allTreatmentOptions = defaultMaterial ? ( defaultMaterial === 'Steel' ? steelTreatments : aluminiumTreatments) : aluminiumTreatments.concat(steelTreatments);
    const selectedCount = lineItems.reduce((total, item) => item.checked ? total + 1 : total, 0);

    useEffect(() => {
        // Collect all required populated data on page load
        Promise.all([
            getProducts(userId, signature),
            getColours(userId, signature),
            getDealLineItems(userId, dealId, signature)
        ]).then((responses) => {
            setProducts(responses[0].data.products.sort(function(a, b) { return a.product_name.localeCompare(b.product_name) }));
            responses[1].data.colours.push({name: 'BLAST ONLY', colour_id: '0'})
            setColours(responses[1].data.colours.sort(function(a, b) { return a.name.localeCompare(b.name) }));
            setColourList(responses[1].data.colours.map(colour => colour.name));
            responses[2].data.lineitems.sort((a,b) => parseInt(a.hs_position_on_quote) - parseInt(b.hs_position_on_quote));
            setLineItems(responses[2].data.lineitems.map(lineitem => {lineitem.total_price = lineitem.quantity * lineitem.price;lineitem.checked = false; return lineitem}));
            setIsLoading(false);
        });
        // https://github.com/jacktaylorgroup/coastal-powder-coating-api/issues/112
    }, []);

    useEffect(() => {
        let cost = 0;
        let countItems = 0;

        lineItems.map(lineItem => {
            cost += lineItem.total_price;
            if (!lineItem.add_product) {
                countItems ++;
            }
        });

        setTotalCost(cost);
        setTotalItems(countItems);
    }, [lineItems]);

    // Manage Default Colour settings
    const onChangeDefaultColour = (e, colour) => {
        setDefaultColour(colour);
    }

    const onChangeDefaultMaterial = (e, material) => {
        setDefaultMaterial(material);
    }

    const onChangeDefaultTreatment = (e, treatment) => {
        setDefaultTreatment(treatment);
    }

    const onChangeDefaultCoatingLine = (e, coatingLine) => {
        setDefaultCoatingLine(coatingLine);
    }

    // Manage adding additional rows
    const handleAddNewRow = () => {
        if (!lineItems[lineItems.length - 1] || !lineItems[lineItems.length - 1].add_product) {
            setIsAdding(true);

            let updatedItems = lineItems;
            updatedItems.push({
                add_product:true,
                quantity: 1,
                colour: defaultColour.name,
                material: defaultMaterial,
                treatment: defaultTreatment,
                coating_line: defaultCoatingLine,
                line_item_id: `temp_${curentTempNumber}`,
                hs_position_on_quote: lineItems.length,
                checked: false,
            });
            setCurentTempNumber(curentTempNumber+1);
            setLineItems(updatedItems);
        }
    }

    const handleEditRow = (index) => {
        const item = lineItems[index];
        item.edit_product = true;
        setLineItems([...lineItems]);
    }

    const addItem = (value) => {
        const currentItems = lineItems;
        const newItem = {
            product: value.product_name,
            product_id: value.product_id,
            quantity: 1,
            price: value.price,
            total_price: value.price,
            colour: defaultColour.name,
            material: defaultMaterial,
            treatment: defaultTreatment,
            coating_line: defaultCoatingLine,
            description: value.description,
            line_item_id: null,
            hs_position_on_quote: currentItems.length -1,
            checked: false,
        }

        currentItems.pop()
        currentItems.push(newItem);

        setLineItems(currentItems);
        setIsAdding(false);
    }


    const deleteItem = (index) => {
        let currentItems = lineItems;
        currentItems.splice(index, 1);
        setLineItems([...currentItems]);
    }

    // Handle editing line item columns
    const updateItem = (index, data, property) => {
        // https://github.com/jacktaylorgroup/coastal-powder-coating-api/issues/104
        let currentItems = lineItems;

        if (property == 'quantity') {
            data.quantity = parseFloat(eval(data.quantity)).toFixed(3);
            if (data.quantity) {
                data.total_price =  currentItems[index].price * data.quantity
            }
        } else if(property == 'price') {
            data.price = eval(data.price);
            data.total_price =  currentItems[index].quantity * data.price
        }

        //If the material changes, then the treatment may become invalid
        if (property === 'material') {
            if (data.material === 'Aluminium') {
                if (!aluminiumTreatments.includes(currentItems[index].treatment)) {
                    data.treatment = "";
                }
            } else {
                if (!steelTreatments.includes(currentItems[index].treatment)) {
                    data.treatment = "";
                }
            }
        }

        if (property === 'treatment') {
            if(!data.treatment.includes('C') && !data.treatment.includes('P')) {
                data.coating_line = 'No Line';
            }
        }


        if (index !== null && index !== undefined) {
            currentItems[index] = {...currentItems[index], ...data};
            if(currentItems[index].hasOwnProperty('add_product')) {
                delete currentItems[index].add_product
            }
            // https://github.com/jacktaylorgroup/coastal-powder-coating-api/issues/105
            setLineItems(JSON.parse(JSON.stringify(currentItems)));
            setIsAdding(false);
        }
    }

    // Handle saving changes to HubSpot
    const handleSave = () => {
        setIsSaving(true);
        setIsAdding(false);

        const data = { lineitems: lineItems.map(item => ({...item, line_item_id: item.line_item_id.includes('temp') ? null : item.line_item_id})) };

        if (data.lineitems[data.lineitems.length - 1] && data.lineitems[data.lineitems.length - 1].hasOwnProperty('add_product')) {
            // If a new line item has been selected by mistake, we don't require it now
            data.lineitems.pop();
        }


        saveChanges(userId, dealId, data, signature).then(response => {
            console.log('response', response);
            getDealLineItems(userId, dealId, signature).then(response => {
                console.log('getResponse', response);
                setLineItems(response.data.lineitems.sort((a,b) => parseInt(a.hs_position_on_quote) - parseInt(b.hs_position_on_quote)));
                setIsSaving(false);
                Swal.fire({
                    title: 'Success',
                    text: 'Deal updated successfully!',
                    icon: 'success',
                    confirmButtonColor: '#3490dc'
                });

            }).catch(error => {
                console.error(error);
                setIsSaving(false);
                Swal.fire({
                    title: 'Error',
                    text: error.response.data.message,
                    icon: 'error',
                    confirmButtonColor: '#3490dc'
                });
            });
        }).catch(error => {
            // TODO https://github.com/jacktaylorgroup/coastal-powder-coating-api/issues/111
            console.error(error);
            setIsSaving(false);
            Swal.fire({
                title: 'Error',
                text: error.response.data.message,
                icon: 'error',
                // confirmButtonColor: '#3490dc'
            });
        });
    }

    const handleBulkEditSave = (selectedColour, selectedMaterial, selectedTreatment, selectedCoatingLine) => {
        const currentItems = lineItems;
        currentItems.forEach((item) => {
            if (item.checked) {
                if (selectedColour) {
                    item.colour = selectedColour;
                }
                if (selectedMaterial) {
                    item.material = selectedMaterial;
                }
                if (selectedTreatment) {
                    item.treatment = selectedTreatment;
                }
                if (selectedCoatingLine) {
                    item.coating_line = selectedCoatingLine;
                }
            }
        });

        setLineItems(currentItems);
        setIsBulkEdit(false);
    }

    return (
        <React.Fragment>
                { // Show Toast
                    showToast && (
                        <div className={`toast-holder toast fade show ${toastType && 'bg-' + toastType}`} role="alert" aria-live="assertive" aria-atomic="true">
                            <div className="toast-header">
                                <div className="mr-auto p-2">{toastMessage}</div>
                                {/* <button onClick={closeToast} type="button" className="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
                                    <span aria-hidden="true">×</span>
                                </button> */}
                            </div>
                        </div>
                    )
                }

                {
                    isBulkEdit && (
                        <BulkEdit
                            colours={colours}
                            isEditing={isBulkEdit}
                            selectedCount={selectedCount}
                            onClose={() => setIsBulkEdit(false)}
                            handleSave={handleBulkEditSave}
                        />
                    )
                }

                <div className="btn-group" role="group">
                    <div>
                        <Autocomplete
                            id="default-colour-search"
                            options={colours}
                            sx={{ width: 180 }}
                            renderInput={(params) => <TextField {...params} label="Default Colour" variant="outlined" />}
                            getOptionLabel={(option) => option.name}
                            onChange={onChangeDefaultColour}
                        />
                    </div>
                    <div>
                        <Autocomplete
                            id="default-material-search"
                            options={materials}
                            sx={{ width: 180 }}
                            renderInput={(params) => <TextField {...params} label="Default Material" variant="outlined" />}
                            onChange={onChangeDefaultMaterial}
                        />
                    </div>
                    <div>
                        <Autocomplete
                            id="default-treatment-search"
                            options={allTreatmentOptions}
                            sx={{ width: 180 }}
                            renderInput={(params) => <TextField {...params} label="Default Treatment" variant="outlined" />}
                            onChange={onChangeDefaultTreatment}
                        />
                    </div>
                    <div>
                        <Autocomplete
                            id="default-bay-search"
                            options={coatingLine}
                            sx={{ width: 180 }}
                            renderInput={(params) => <TextField {...params} label="Default Powder Bay" variant="outlined" />}
                            onChange={onChangeDefaultCoatingLine}
                        />
                    </div>
                </div>
                <br />
                <br />
                <br />

                <div className="">
                    {
                        (isLoading) && (
                            <div className="text-center loader-container bg-light p-2 rounded-sm">
                                <div className="spinner-border" role="status">
                                </div>
                                <p>Loading...</p>
                            </div>
                        )
                    }
                    <Table
                        items={lineItems}
                        products={products}
                        colours={colours}
                        colourList={colourList}
                        // defaultColour={defaultColour}
                        // defaultMaterial={defaultMaterial}
                        // defaultTreatment={defaultTreatment}
                        // defaultCoatingLine={defaultCoatingLine}
                        isSaving={isSaving}
                        isAdding={isAdding}
                        setIsAdding={setIsAdding}
                        isLoading={isLoading}
                        addnewRow={handleAddNewRow}
                        editRow={handleEditRow}
                        addItem={addItem}
                        updateItem={updateItem}
                        deleteItem={deleteItem}
                        setLineItems={setLineItems}
                        // handleDelete={handleDelete}
                        // handleEdit={handleEdit}

                        /***
                         * The props below were included from the previous edition of the coastal powder integrations. I am not sure if there are still required
                         * https://bitbucket.org/jts-cloud/coastalpowder/src/master/
                         */

                        // updateAllItem={updateAllItem}
                        // openToast={openToast}

                        // showColourResult={showColourResult}
                        // onInput={onInput}
                        // addRow={addRow}
                        // productOnBlur={productOnBlur}
                        // onFocusOutDefaultColour={onFocusOutDefaultColour}
                        // setShowColourResult={setShowColourResult}
                    />
                </div>

                <div className="total d-flex justify-content-around">
                    <span>Total Items: <b>{totalItems}</b></span>
                    <span>Subtotal: <b>{currencyFormat(totalCost)}</b></span>
                    <span>Total Inc GST: <b>{currencyFormat(totalCost*1.1)}</b></span>
                </div>

                <div className="d-flex justify-content-start">
                    <div className="mb-4 btn-group">
                        {/* ADD NEW ITEM BUTTON */}
                        <button
                            type="button"
                            onClick={handleAddNewRow}
                            disabled={isAdding || isSaving}
                            className="btn btn-primary mr-2 text-white"
                        >
                            <i className="fa fa-plus"></i> Add New Item
                        </button>

                        {/* Bulk Edit */}
                        <button
                            type="button"
                            onClick={() => setIsBulkEdit(!isBulkEdit)}
                            disabled={isAdding || isSaving}
                            className="btn btn-primary mr-2 text-white"
                        >
                            <i className="fa fa-edit"></i> Bulk Edit
                        </button>


                        {/* SAVE BUTTON */}
                        <button
                            type="button"
                            onClick={handleSave}
                            disabled={isSaving || isLoading}
                            className="btn btn-primary text-white"
                        >

                            {/* https://github.com/jacktaylorgroup/coastal-powder-coating-api/issues/108 */}
                            {
                                isSaving ? (
                                    <React.Fragment>
                                        <span className="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                        &nbsp;Saving...
                                    </React.Fragment>
                                ) : (
                                    <React.Fragment>
                                    <i className="fa fa-save"></i> Save
                                    </React.Fragment>
                                )
                            }
                        </button>


                    </div>
                </div>
            </React.Fragment>
    )

};


export default DealLineItemEditor;

if (document.getElementById('deal-line-item-editor')) {
    const container = document.getElementById('deal-line-item-editor');
    const root = createRoot(container);
    root.render(<DealLineItemEditor />);
}
