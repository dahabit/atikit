<?php
/**
 * aTikit v1.0 by Core 3 Networks (www.core3networks.com)
 *
 * Copyright (c) 2013 Core 3 Networks, Inc and Chris Horne <chorne@core3networks.com>
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A
 * PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @package atikit10
 * @trait c3stripe
 */
trait c3stripe
{
	public function stripe_chargeCustomer($amount, $description, $cid = null)
	{
		if (!$cid)
			$cid = $this->company->id;
		$customer = $this->returnFieldFromTable("company_stripeid", "companies", "id='{$cid}'");
		if (!$customer)
		{
			$this->log("Attempting to Charge a Credit Card without a Customer Account.", 'stripe');
			return false;
		}
		$this->log("Attempting to Charge $amount to $customer for $description", "stripe");
		
		try
		{
			Stripe_Charge::create(array(
			"amount" => $amount,
			"currency" => "usd",
			"customer" => $customer, 
			"description" => $description)
			);
			return true;
		}
		catch (Exception $e)
		{
			$this->log("Charged Failed:  ". $e->getMessage(), 'stripe');
			return $e->getMessage();
		} 
	}
	
	
	public function stripe_invoiceItem($amount, $description, $cid = null)
	{
		if (!$cid)
			$cid = $this->company->id;
		$customer = $this->returnFieldFromTable("company_stripeid", "companies", "id='{$cid}'");
		$this->log("Attempting to add to Invoice $amount to $customer for $description", 'stripe');
		try 
		{
			Stripe_InvoiceItem::create(array(
			"customer" => $customer,
			"amount" => $amount,
			"currency" => "usd",
			"description" => $description)
			);
		return true;
		}
		catch (Exception $e)
		{
			$this->log("Invoice Creation Failed:  ". $e->getMessage(), 'stripe');
			return $e->getMessage();
		}	
		
	}
	
	public function stripe_getUpcoming ($cid)
	{
		if (!$cid)
			$cid = $this->company->id;
		$customer = $this->returnFieldFromTable("company_stripetoken", "companies", "id='{$cid}'");
		return Stripe_Invoice::upcoming(array("customer" => $customer));
	}
	
	
	
	public function stripe_updateCustomer($token, $cid = null, $coupon = null)
	{
	    if (!$cid)
			$cid = $this->company->id;
		$company = $this->query("SELECT * from companies WHERE id='$cid'")[0];
	    $user = $this->query("SELECT * from users WHERE company_id='$cid'")[0];
		$customer = $company['company_stripeid'];	
		$cu = Stripe_Customer::retrieve($customer);
		$cu->card = $token;
		if ($coupon)
			$cu->coupon = $coupon;
		try
		{
			$cu->save();
			return true;
		}
		catch (exception $e)
		{
			$this->log("Update Customer Failed:  ". $e->getMessage(), 'stripe');
			return false;
		}
	}
	
	public function stripe_getPlans()
	{
		$plans = Stripe_Plan::all();
		$res = [];
		foreach ($plans['data'] AS $plan)
			$res[] = $plan;
		return $res;
	}
	
	public function stripe_getPlanObjects()
	{
		return Stripe_Plan::all();
	}
	
	public function stripe_getCoupons()
	{
		$coupons = Stripe_Coupon::all();
		$res = [];
		foreach ($coupons['data'] AS $coupon)
			$res[] = $coupon;
		return $res;
	}

	public function stripe_createCoupon($params)
	{
		try {
			Stripe_Coupon::create($params);
			return true;	
		}
		catch (exception $e)
		{
			return $e->getMessage();
		}
		
		
	}
	
	public function stripe_createPlan($params)
	{
				/*
				 * Stripe_Plan::create(array(
		  "amount" => 2000,
		  "interval" => "month",
		  "name" => "Amazing Gold Plan",
		  "currency" => "usd",
		  "id" => "gold"));
				 */
		
		try 
		{
			Stripe_Plan::create([
			'amount' => ($params['amount'] * 100),
			'interval' => $params['interval'],
			'name' => $params['name'],
			'interval_count' => $params['count'],
			'currency' => 'usd',
			'id' => $params['id'],
			'trial_period_days' => $params['trial']
			]);	
			return true;
			
		}
		catch (Exception $e)
		{
			$this->log("Unable to Create Plan:  ". $e->getMessage(), 'stripe');
			return $e->getMessage();			
		}		
		
	}
	
	
	public function stripe_setPlan($plan, $cid)
	{
		$customer = $this->returnFieldFromTable("company_stripeid", "companies", "id='{$cid}'");
		try 
		{
			$c = Stripe_Customer::retrieve($customer);
			$c->updateSubscription(array("prorate" => true, "plan" => $plan));
			return true;
		}
		catch (Exception $e)
		{
			$this->log("Unable to Set Plan: (CID: $cid) ". $e->getMessage(), 'stripe');
			return $e->getMessage();	
			
		}
	}
	
	public function stripe_getPlan($id)
	{
		$plan = Stripe_Plan::retrieve($id);
		return $plan;
	}
	
	public function stripe_getCustomerPlan($id = null)
	{
		if (!$id) $id = $this->company->id;
		$customer = $this->returnFieldFromTable("company_stripeid", "companies", "id='{$id}'");
		try
		{
			$cu = Stripe_Customer::retrieve($customer);
			$plan = $cu->subscription;
			$plan = $plan['plan'];
			return $plan;
		}
		catch (Exception $e)
		{
			return false;
			
		}
		
	}
	
	public function stripe_createCustomer($token, $cid = null)
	{
	    if (!$cid)
			$cid = $this->company->id;
		$company = $this->query("SELECT * from companies WHERE id='$cid'")[0];
	    $user = $this->query("SELECT * from users WHERE company_id='$cid'")[0];
	    
		try
		{
			$pkg = [];
			$pkg['description'] = $company['company_name'];
			$pkg['card'] = $token;
			$pkg['email'] = $user['user_email'];
			$data = Stripe_Customer::create($pkg);
			$data = json_decode($data);
			
		}
		catch (exception $e)
		{
			$this->log($e->getMessage());
			
		}
		return $data->id;
	}
	
	
	public function stripe_getInvoices($user)
	{
		
		$customer = $this->returnFieldFromTable("company_stripetoken", "companies", "id='$user'");
		try {
			$data = Stripe_Invoice::all(array(
		"customer" => $customer));
		}
		catch (exception $e)
		{
			
		}
		return $data;		
	}
	
	public function stripe_getInvoice($invoice)
	{
		try 
		{
			$inv = Stripe_Invoice::retrieve($invoice);
			return $inv;
		}
		catch (Exception $e)
		{
			return false;
		}
	}
	
	
	
	public function stripe_cancelSubscription($cid)
	{
		$customer = $this->returnFieldFromTable("company_stripeid", "companies", "id='$cid'");
		
		try
		{
			$cu = Stripe_Customer::retrieve($customer);
			$cu->cancelSubscription();
			return true;
		}
		catch (exception $e)
		{
			return $e->getMessage();
		}
		
	}
	
}