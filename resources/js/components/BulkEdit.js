import React, { useState } from 'react';
import { Autocomplete, TextField, Modal, Box } from '@mui/material';
import { aluminiumTreatments, steelTreatments, coatingLine, materials } from '../constants';

const BulkEdit = (props) => {

    const [selectedColour, setSelectedColour] = useState('');
    const [selectedMaterial, setSelectedMaterial] = useState('');
    const [selectedTreatment, setSelectedTreatment] = useState('');
    const [selectedCoatingLine, setSelectedCoatingLine] = useState('');

    const colours = props.colours;
    const allTreatmentOptions = selectedMaterial ? ( selectedMaterial === 'Steel' ? steelTreatments : aluminiumTreatments) : aluminiumTreatments.concat(steelTreatments);
    const selectedCount = props.selectedCount;

    const style = {
        position: 'absolute',
        top: '50%',
        left: '50%',
        transform: 'translate(-50%, -50%)',
        // width: 400,
        bgcolor: 'background.paper',
        border: '2px solid #000',
        boxShadow: 24,
        // p: 4,
    };

    return (
        <Modal open={props.isEditing} onClose={props.onClose} >
            <Box sx={style}>
                <div>
                    <span>Editing {selectedCount} items</span>
                </div>
                <div className="btn-group-vertical" role="group">
                    <div>
                        <Autocomplete
                            id="bulk-colour-search"
                            options={colours}
                            sx={{ width: 180 }}
                            renderInput={(params) => <TextField {...params} label="Default Colour" variant="outlined" />}
                            getOptionLabel={(option) => option.name}
                            onChange={(event, value) => setSelectedColour(value.name)}
                        />
                    </div>
                    <div>
                        <Autocomplete
                            id="default-material-search"
                            options={materials}
                            sx={{ width: 180 }}
                            renderInput={(params) => <TextField {...params} label="Default Material" variant="outlined" />}
                            onChange={(event, value) => setSelectedMaterial(value)}
                        />
                    </div>
                    <div>
                        <Autocomplete
                            id="default-treatment-search"
                            options={allTreatmentOptions}
                            sx={{ width: 180 }}
                            renderInput={(params) => <TextField {...params} label="Default Treatment" variant="outlined" />}
                            onChange={(event, value) => setSelectedTreatment(value)}
                        />
                    </div>
                    <div>
                        <Autocomplete
                            id="default-bay-search"
                            options={coatingLine}
                            sx={{ width: 180 }}
                            renderInput={(params) => <TextField {...params} label="Default Powder Bay" variant="outlined" />}
                            onChange={(event, value) => setSelectedCoatingLine(value)}                        />
                    </div>
                </div>
                <div>
                    <button className="btn btn-primary" onClick={() => props.handleSave(selectedColour, selectedMaterial, selectedTreatment, selectedCoatingLine)}>Save</button>
                </div>
            </Box>
        </Modal>
    )
}

export default BulkEdit;