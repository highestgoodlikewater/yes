<?php

namespace amilna\yes\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use amilna\yes\models\Order;

/**
 * OrderSearch represents the model behind the search form about `amilna\yes\models\Order`.
 */
class OrderSearch extends Order
{

	
	/*public $confirmationsId;*/
	public $customerName;
	/*public $salesId;*/

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'customer_id', 'status', 'isdel'], 'integer'],
            [['reference', 'customerName','total', 'data', 'time', 'complete_reference', 'complete_time', 'log'/*, 'confirmationsId', 'customerId', 'salesId'*/], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

	private function queryString($fields)
	{		
		$params = [];
		foreach ($fields as $afield)
		{
			$field = $afield[0];
			$tab = isset($afield[1])?$afield[1]:false;			
			if (!empty($this->$field))
			{				
				array_push($params,["like", "lower(".($tab?$tab.".":"").$field.")", strtolower($this->$field)]);
			}
		}	
		return $params;
	}	
	
	private function queryNumber($fields)
	{		
		$params = [];
		foreach ($fields as $afield)
		{
			$field = $afield[0];
			$tab = isset($afield[1])?$afield[1]:false;			
			if (!empty($this->$field))
			{				
				$number = explode(" ",$this->$field);			
				if (count($number) == 2)
				{									
					array_push($params,[$number[0], ($tab?$tab.".":"").$field, $number[1]]);	
				}
				elseif (count($number) > 2)
				{															
					array_push($params,[">=", ($tab?$tab.".":"").$field, $number[0]]);
					array_push($params,["<=", ($tab?$tab.".":"").$field, $number[0]]);
				}
				else
				{					
					array_push($params,["=", ($tab?$tab.".":"").$field, str_replace(["<",">","="],"",$number[0])]);
				}									
			}
		}	
		return $params;
	}
	
	private function queryTime($fields)
	{		
		$params = [];
		foreach ($fields as $afield)
		{
			$field = $afield[0];
			$tab = isset($afield[1])?$afield[1]:false;			
			if (!empty($this->$field))
			{				
				$time = explode(" - ",$this->$field);			
				if (count($time) > 1)
				{								
					array_push($params,[">=", "concat('',".($tab?$tab.".":"").$field.")", $time[0]]);	
					array_push($params,["<=", "concat('',".($tab?$tab.".":"").$field.")", $time[1]." 24:00:00"]);
				}
				else
				{
					if (substr($time[0],0,2) == "< " || substr($time[0],0,2) == "> " || substr($time[0],0,2) == "<=" || substr($time[0],0,2) == ">=") 
					{					
						array_push($params,[str_replace(" ","",substr($time[0],0,2)), "concat('',".($tab?$tab.".":"").$field.")", trim(substr($time[0],2))]);
					}
					else
					{					
						array_push($params,["like", "concat('',".($tab?$tab.".":"").$field.")", $time[0]]);
					}
				}	
			}
		}	
		return $params;
	}

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Order::find();
        
                
        $query->joinWith(['customer'/*'confirmations', 'customer', 'sales'*/]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        
        
        $dataProvider->sort->attributes['customerName'] = [			
			'asc' => ['{{%yes_customer}}.name' => SORT_ASC],
			'desc' => ['{{%yes_customer}}.name' => SORT_DESC],
		];
		
        /* uncomment to sort by relations table on respective column
		$dataProvider->sort->attributes['confirmationsId'] = [			
			'asc' => ['{{%confirmations}}.id' => SORT_ASC],
			'desc' => ['{{%confirmations}}.id' => SORT_DESC],
		];		
		$dataProvider->sort->attributes['salesId'] = [			
			'asc' => ['{{%sales}}.id' => SORT_ASC],
			'desc' => ['{{%sales}}.id' => SORT_DESC],
		];*/

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }				
		
        $params = self::queryNumber([['id'],['customer_id'],['total'],['status'],['isdel']/*['id','{{%confirmations}}'],['id','{{%customer}}'],['id','{{%sales}}']*/]);
		foreach ($params as $p)
		{
			$query->andFilterWhere($p);
		}
        $params = self::queryString([['reference'],['data'],['complete_reference'],['log']/*['id','{{%confirmations}}'],['id','{{%customer}}'],['id','{{%sales}}']*/]);
		foreach ($params as $p)
		{
			$query->andFilterWhere($p);
		}
        $params = self::queryTime([['time'],['complete_time']/*['id','{{%confirmations}}'],['id','{{%customer}}'],['id','{{%sales}}']*/]);
		foreach ($params as $p)
		{
			$query->andFilterWhere($p);
		}
		
		$query->andFilterWhere(['like','lower({{%yes_customer}}.name)',strtolower($this->customerName)]);
		
        return $dataProvider;
    }
}
