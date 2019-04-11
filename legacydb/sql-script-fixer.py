# This script fixes the .sql files Ken sent us, this file only needs to run once.
# Most likely this has already been run, but I am adding it here for historic record purposes

import os, re

# Assumes that the original .sql script files are in a folder called wioa
os.chdir('wioa/')

for file in directory:
    open_file = open(file,'r')
    read_file = open_file.read()

    # Read state and year from file name, since that is the only place where we can get this info
    state = file[0:2]
    year = file[3:7]

    # Add state and year to the INSERT and VALUES statements in every .sql file
    regex = re.compile('\(grant_award_id,')
    read_file = regex.sub('(state, year, grant_award_id, ', read_file)
    regex = re.compile('VALUES \(')
    read_file = regex.sub("VALUES ('%s', '%s', " % (state, year), read_file)

    # Add semicolon at the end of every DELETE line
    regex = re.compile('current_row = \d+')
    read_file = regex.sub('current_row = 1;', read_file)

    # Add semicolon at the end of every INSERT line
    regex = re.compile("' \)")
    read_file = regex.sub("' );", read_file)

    # Remove CONVERT and use UNIX_TIMESTAMP instead so we can use the same timestamp that Drupal uses
    regex = re.compile('CONVERT\(datetime,')
    read_file = regex.sub('UNIX_TIMESTAMP(', read_file)

    # Add use statement to the beginning of every file so it know where to execute the following statements
    write_file = open(file,'w')
    write_file.seek(0,0)
    write_file.write('USE `wioa_dump`;' + '\n' + read_file)

    write_file.close()