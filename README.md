# zuora-bad-debt

1) Receive Write Off CSV from Finance
2) Update CSV formatting to be CSV with columns
  - Acc Name
  - Acc Account Number
  - Acc Currency
  - Invoice Number 
  - Invoice Balance
  - Invoice Date
3) Login to ZUORA (https://www.zuora.com/apps/newlogin.do)
  - if unable to log in please contact Rhys for 2FA
4) Update .env 
   - CSVINVCOL equals Invoice Number within the above CSV
     left most column is 0
   - WRITE_OFF_DATE equals TODAY unless specified, check e mail from Finance for date.
   - Identify working directory for CSV in .env
     e.g. filepath /Users/bradley/Downloads/End of Month/ZBD/June 2018/write_off_list_june_2018.csv
5) Run terminal
    navigate to working directory of ZBD
    e.g. /Users/bradley/IdeaProjects/github/zuora-bad-debt
    php app.php 
    follow prompts
6) Copy output to .groovy file
   e.g. Zuora Bad Debt June 2018 Script.groovy
7) Send .groovy output file back to Finance upon completion