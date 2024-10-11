import smartsheet
import pandas as pd
import math
from openpyxl import load_workbook
from datetime import datetime

# Smartsheet API token
api_token = "52ej422t7cs6uxk8jlytqsl41k"

# Initialize the Smartsheet client
ss_client = smartsheet.Smartsheet(api_token)

# Specify the target sheet ID
sheet_id = 2247499838345092

# Define column mappings from XLSX columns to Smartsheet column IDs
column_mappings = {
    "WorkTicketKey": 1247617916030852,
    "WorkTicketNo": 5751217543401348,
    "WorkCenter": 3499417729716100,
    "StepNo": 8003017357086596,
    "StatusCode": 684667962609540,
    "WorkTicketDate": 5188267589980036,
    "QuantityOrdered": 2936467776294788,
    "QuantityPlanned": 7440067403665284,
    "QuantityCompleted": 1810567869452164,
    "QuantityScrapped": 6314167496822660,
    "QuantityMadeForWorkTicket": 4062367683137412,
    "MakeForSalesOrderNo": 8565967310507908,
    "ParentItemCode": 403192985898884,
    "ParentItemCodeDesc": 4906792613269380,
    "EffectiveDate": 2654992799584132,
    "ActualReleaseDate": 7158592426954628,
    "ExpectedReleaseDate": 1529092892741508,
    "ProductionStartDate": 6032692520112004,
    "ProductionDueDate": 3780892706426756,
    "ParentUnitOfMeasure": 8284492333797252,
    "DateCreated": 966142939320196,
    "TimeCreated": 5469742566690692,
    "DateUpdated": 3217942753005444,
    "UDF_CUSTOMER_NO": 7721542380375940,
    "UDF_CUSTOMER_PO_NO": 2092042846162820,
    "UDF_HGRADE": 6595642473533316,
    "UDF_HLENGTH": 4343842659848068,
    "UDF_HLOT_NO": 8847442287218564,
    "UDF_HTHICKNESS": 262455497543556,
    "UDF_HTOTAL_WEIGHT": 4766055124914052,
    "UDF_HWIDTH": 2514255311228804,
    "PO - PART # Helper": 7017854938599300
}

# Load the XLSX file into a Pandas DataFrame
xlsx_file = "wtexport.xlsx"
wb = load_workbook(xlsx_file)
sheet = wb.active
data = sheet.values
columns = next(data)
df = pd.DataFrame(data, columns=columns)

# Get the target sheet
sheet = ss_client.Sheets.get_sheet(sheet_id)

# Initialize a list to store modified rows
modified_rows = []

# Iterate through each row in the XLSX file
for index, row_data in df.iterrows():
    unique_value = row_data["PO - PART # Helper"]

    # Find the corresponding row in Smartsheet
    for smartsheet_row in sheet.rows:
        unique_cell = smartsheet_row.get_column(column_mappings["PO - PART # Helper"])
        if unique_cell and unique_cell.value == unique_value:
            # Update the specified columns' cell values
            modified_row = smartsheet.models.Row()
            modified_row.id = smartsheet_row.id

            for column_name, column_id in column_mappings.items():
                if column_name != "PO - PART # Helper":
                    cell_value = row_data[column_name]

                    # Create a new cell object
                    cell = smartsheet.models.Cell()
                    cell.column_id = column_id

                    # Convert date values to ISO 8601 format (YYYY-MM-DD) if they are of type DATE
                    if column_name in ["WorkTicketDate", "EffectiveDate", "ActualReleaseDate", "ProductionStartDate", "ProductionDueDate", "DateCreated", "DateUpdated"]:
                        try:
                            date_value = pd.to_datetime(cell_value, errors='coerce')
                            if pd.notna(date_value):
                                cell.value = date_value.strftime("%Y-%m-%d")
                            else:
                                cell.value = ''
                        except Exception as e:
                            cell.value = ''
                    else:
                        # Check for NaN and replace it with an empty string
                        if pd.isna(cell_value):
                            cell.value = ''
                        else:
                            cell.value = str(cell_value)

                    modified_row.cells.append(cell)

            # Append the modified row to the list
            modified_rows.append(modified_row)
            break  # Exit the loop after updating the row

# Update rows in batches to reduce API calls
batch_size = 100  # Adjust the batch size as needed
for i in range(0, len(modified_rows), batch_size):
    batch = modified_rows[i:i + batch_size]
    try:
        response = ss_client.Sheets.update_rows(sheet_id, batch)
        print(f"Updated {len(batch)} rows in the batch successfully.")
    except smartsheet.exceptions.SmartsheetError as e:
        print(f"Error while updating rows: {e}")
