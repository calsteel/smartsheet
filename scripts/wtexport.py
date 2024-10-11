import pyodbc
from openpyxl import Workbook

# Updated DSN connection with corrected parameters
DSN_NAME = "MASCSS"

# Connect to the database using the DSN
cnxn = pyodbc.connect(
    f"DSN={DSN_NAME};"
    "UID=scanco;"
    "PWD=;"
    r"Directory=\\mas2023\Sage\Sage 100\MAS90;"
    r"Prefix=\\mas2023\Sage\Sage 100\MAS90\SY\,\\mas2023\Sage\Sage 100\MAS90\==\;"
    r"ViewDLL=\\mas2023\Sage\Sage 100\MAS90\HOME;"
    "Company=CSS;"
    "SID=446;"
    r"LogFile=PVXODBC.LOG;"
    "CacheSize=4;"
    "DirtyReads=1;"
    "BurstMode=1;"
    "StripTrailingSpaces=1;",
    autocommit=True
)

cursor = cnxn.cursor()


# SQL query to select all rows from the table PM_WorkTicketHeader and PM_WorkTicketStep using comma-separated tables
detail_query = """
SELECT PM_WorkTicketHeader.WorkTicketKey, PM_WorkTicketHeader.WorkTicketNo, 
       PM_WorkTicketStep.WorkCenter, PM_WorkTicketStep.StepNo, 
       PM_WorkTicketStep.StatusCode, PM_WorkTicketStep.LineKey, PM_WorkTicketHeader.WorkTicketDate, 
       PM_WorkTicketHeader.QuantityOrdered, PM_WorkTicketHeader.QuantityPlanned, 
       PM_WorkTicketHeader.QuantityCompleted, PM_WorkTicketHeader.QuantityScrapped, 
       PM_WorkTicketHeader.QuantityMadeForWorkTicket, PM_WorkTicketHeader.MakeForSalesOrderNo, 
       PM_WorkTicketHeader.ParentItemCode, PM_WorkTicketHeader.ParentUnitOfMeasure, PM_WorkTicketHeader.ParentItemCodeDesc, 
       PM_WorkTicketHeader.EffectiveDate, PM_WorkTicketHeader.ActualReleaseDate, 
       PM_WorkTicketHeader.ExpectedReleaseDate, PM_WorkTicketHeader.ProductionStartDate, 
       PM_WorkTicketHeader.ProductionDueDate, 
       PM_WorkTicketHeader.DateCreated, PM_WorkTicketHeader.TimeCreated, 
       PM_WorkTicketHeader.DateUpdated,  
       PM_WorkTicketHeader.UDF_CUSTOMER_NO, PM_WorkTicketHeader.UDF_CUSTOMER_PO_NO, 
       PM_WorkTicketHeader.UDF_HGRADE, PM_WorkTicketHeader.UDF_HLENGTH, 
       PM_WorkTicketHeader.UDF_HLOT_NO, PM_WorkTicketHeader.UDF_HTHICKNESS, 
       PM_WorkTicketHeader.UDF_HTOTAL_WEIGHT, PM_WorkTicketHeader.UDF_HWIDTH
FROM PM_WorkTicketHeader, PM_WorkTicketStep
WHERE PM_WorkTicketStep.WorkTicketKey = PM_WorkTicketHeader.WorkTicketKey

"""
cursor.execute(detail_query)

# Fetch the results from the query
detail_rows = cursor.fetchall()

# Specify the XLSX file path for the output
DETAILS_XLSX = "wtexport.xlsx"

# Create a new Excel workbook and select the active sheet
workbook = Workbook()
sheet = workbook.active

# Write the header row
header = [column[0] for column in cursor.description]
header.extend(["PO - PART # Helper"])
sheet.append(header)

# Modify data rows and add them to the sheet
for row in detail_rows:
    # Remove leading zeros and perform data modifications as needed
    work_ticket_no = str(row.WorkTicketKey).lstrip('0')
    step_no = str(row.LineKey).lstrip('0')

    # Generate helper fields
    po_part_helper = f"{work_ticket_no} - {step_no}"


    # Convert the row to a list and append the new column values
    modified_row = list(row)
    modified_row.extend([po_part_helper])
    modified_row[0] = work_ticket_no  # Update WorkTicketNo
    modified_row[5] = step_no  # Update StepNo

    sheet.append(modified_row)

# Save the workbook to the XLSX file
workbook.save(DETAILS_XLSX)

print(f"Data from PM_WorkTicketHeader and PM_WorkTicketStep exported to {DETAILS_XLSX}")
