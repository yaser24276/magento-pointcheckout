# Point-checkout
PointCheckout extension for magento 1.9 setup guide

This guide walks you through on how to setup PointCheckout as a payment method on magento 1.9

	1.	Download the extension zip file from releases tab
	2. Follow the Megento extension setup guides
      •	Log in to Magento admin panel
      •	Check Cache Management (if there are some Cache Types disabled change them to enable status.
      •	Under System -> Cache Management
      •	Check Compilation status (Disable compilation if it's enabled) 
      •	Under System -> tools -> Compilation
      •	Extract the extension zip file and copy the extracted files to the folder of your Magento (Using FTP client)
      •	Return to your Magento admin panel and Flush Cache Storage and Flush Javascript/CSS Cache.
      •	Under System -> Cache Management
      •	Go to System/Tools/Compilation and Enable compilation
      •	Log out from Magento 
      •	Log in to admin panel again and you should see the extension setup UI under System -> Configuration -> Payment Methods
	3.	set configuration for the extension: 
      •	Live or Test Mode: that to choose between live and test mode
      •	API Key:  The API Key Provided by PointCheckout
      •	API Secret Key: The API Secret Key Provided by PointCheckout
      •	Payment Applicable From: All countries / Specific countries
      •	If Specific countries selected, a multi-select list is shown to select the applicable countries
      •	Restrict to specific customer groups: Yes / No
      •	If Yes, a multi-select list of all Customer groups is shown to select the target groups
      •	New order status: the status of the order once the customer is redirected to PointCheckout for processing the payment
      •	Payment success status: the status of the order if payment successfully processed 



