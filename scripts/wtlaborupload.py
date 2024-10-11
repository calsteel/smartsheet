import smartsheet
import pandas as pd

# Smartsheet API token
api_token = "52ej422t7cs6uxk8jlytqsl41k"

# Initialize the Smartsheet client
ss_client = smartsheet.Smartsheet(api_token)

# Specify the target sheet ID
sheet_id = 2247499838345092

# Define column mappings from XLSX columns to Smartsheet column IDs
column_mappings = {
    "PO - PART # Helper": 7017854938599300
}

# Load the XLSX file into a Pandas DataFrame
xlsx_file = "wtlaborexport.xlsx"
df = pd.read_excel(xlsx_file)

# Add a new column 'Uploaded' to track whether the row has been uploaded
if 'Uploaded' not in df.columns:
    df['Uploaded'] = ""  # Initialize as empty if the column doesn't exist
    print("Added 'Uploaded' column to the DataFrame")

# Ensure 'Uploaded' column is set to object dtype to handle strings
df['Uploaded'] = df['Uploaded'].astype(str)

# Get the target sheet from Smartsheet
sheet = ss_client.Sheets.get_sheet(sheet_id)

# Iterate through each row in the XLSX file
for index, row_data in df.iterrows():
    # Skip rows that have already been uploaded
    if row_data.get('Uploaded') == 'Yes':
        print(f"Row {index + 1} already uploaded. Skipping.")
        continue

    unique_value = row_data["PO - PART # Helper"]

    # Find the corresponding row in Smartsheet
    for smartsheet_row in sheet.rows:
        unique_cell = smartsheet_row.get_column(column_mappings["PO - PART # Helper"])
        if unique_cell and unique_cell.value == unique_value:
            # Add a discussion to the row in Smartsheet using the "comment" from Excel
            status_comment = row_data.get("comment", "")
            if status_comment:
                discussion = smartsheet.models.Discussion({
                    'title': f'{row_data["FirstName"]} {row_data["LastName"]}',
                    'comment': smartsheet.models.Comment({
                        'text': status_comment,
                    })
                })

                try:
                    response = ss_client.Discussions.create_discussion_on_row(
                        sheet_id,
                        smartsheet_row.id,
                        discussion
                    )
                    print(f"Discussion added successfully to row {smartsheet_row.id}. Discussion ID: {response.result.id}")

                    # Mark the row as uploaded in the DataFrame
                    df.at[index, 'Uploaded'] = 'Yes'
                    print(f"Marked row {index + 1} as 'Uploaded'")

                except smartsheet.exceptions.ApiError as e:
                    print(f"Error while adding discussion: {e}")

            break  # Exit the loop after adding the discussion to the matched row

# Save the updated DataFrame back to the Excel file
with pd.ExcelWriter(xlsx_file, engine='openpyxl', mode='w') as writer:
    df.to_excel(writer, index=False, sheet_name='Sheet1')
    print(f"Excel file '{xlsx_file}' has been updated with the 'Uploaded' status.")
