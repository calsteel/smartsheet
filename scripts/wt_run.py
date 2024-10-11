import subprocess

# Run csvexport.py
#csvexport_process = subprocess.Popen(["python", "csvexport.py"])
#csvexport_process.wait()  # Wait for the csvexport.py script to finish

# Run new.py
soorder_process = subprocess.Popen(["python", "wtexport.py"])
soorder_process.wait()  # Wait for the soorder.py script to finish


# Run smartsheet.py
smartsheet_process = subprocess.Popen(["python", "wtsmart.py"])
smartsheet_process.wait()  # Wait for the soorder.py script to finish

# Run smartsheet.py
smartsheet_process11 = subprocess.Popen(["python", "wtupdate.py"])
smartsheet_process11.wait()  # Wait for the soorder.py script to finish


# Run vItem.py
attachments_process = subprocess.Popen(["python", "wtlaborexport.py"])
attachments_process.wait()  # Wait for the vItem.py script to finish

# Run attachments3.py
#attachments_process12 = subprocess.Popen(["python", "attachmentlocal.py"])
#attachments_process12.wait()  # Wait for the soorder.py script to finish



# Run attachments.py
attachments_process2 = subprocess.Popen(["python", "wtlaborupload.py"])
attachments_process2.wait()  # Wait for the soorder.py script to finish
