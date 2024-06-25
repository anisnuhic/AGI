#!/usr/bin/env python
#An example for AGI (Asterisk Gateway Interface)

import sys

def agi_command(cmd):
    '''Write out the command and return the response'''
    print (cmd)
    sys.stdout.flush() #clear the buffer
    return sys.stdin.readline().strip() #strip whitespace
asterisk_env = {} #read AGI env vars from asterisk
while True:
        line = sys.stdin.readline().strip()
        if not len(line):
               break
        var_name, var_value = line.split(':',1)
        asterisk_env[var_name] = var_value

#Fake "database" of accounts.
ACCOUNTS = {
      '12345678': {'balance': '50'},
      '11223344': {'balance': '10'},
      '87654321': {'balance': '100'},
}

response = agi_command('ANSWER')

#three arguments: prompt, timeout, maxlength
response = agi_command('GET DATA enter_account 3000 8')

if 'timeout' in response:
      response = agi_command('STREAM FILE goodbye ""')
      sys.exit(0)

#The response will look like: 200 result=<digits>
#Split on '=', we want index 1
account = response.split('=',1)[1]

if account == '-1':
        response = agi_command('STREAM FILE astcc-account-number-invalid ""')
        response = agi_command('HANGUP')
        sys.exit(0)

if account not in ACCOUNTS: #invalid
       response = agi_command('STREAM FILE astcc-account-number-invalid ""')
       sys.exit(0)

balance = ACCOUNTS[account]['balance']

response = agi_command('STREAM FILE account-balance-is ""')
response = agi_command('SAY NUMBER %s ""' % (balance))
sys.exit(0)
