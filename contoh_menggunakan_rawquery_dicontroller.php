<?php

namespace Encore\Admin\Controllers;

use Encore\Admin\Admin;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class Chart
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public static function insla_outsla()
    {
        $insla = DB::connection('sqlsrv')->select("
        DECLARE @sla INT
SET @sla = 6

select
  'IN SLA' sla,
  sum(DF) as 'DispenserFailure',
  sum(CO) as 'CashOut',
  sum(CF) as 'CashFull',
  sum(RECEIPT) as 'ReceiptLowOut',
  sum(Replace_Part) as 'ReplacePart',
  sum(cek) as 'CheckClean'
from (
  select
    acc.ZonalCode as Kanwil,
    case when current_ticket.Error = 'DF' then 1 else 0 end as 'DF',
    case when current_ticket.Error = 'CO' then 1 else 0 end as 'CO',
    case when current_ticket.Error = 'CF' then 1 else 0 end as 'CF',
    case when current_ticket.Error = 'RECEIPT' then 1 else 0 end as 'RECEIPT',
    case when current_ticket.Error = 'Replace_Part' then 1 else 0 end as 'Replace_Part',
    case when current_ticket.Error = 'cek' then 1 else 0 end as 'cek',
    case when current_ticket.Error = 'Network' then 1 else 0 end as 'Network',
    case when current_ticket.Error = 'Host' then 1 else 0 end as 'Host',
    case when current_ticket.Error = 'Electrical' then 1 else 0 end as 'Electrical',
    case when current_ticket.Error = 'Vandalism' then 1 else 0 end as 'Vandalism',
    case when current_ticket.Error = 'ForceMajeure' then 1 else 0 end as 'ForceMajeure',
    case when current_ticket.aging_flm > 2 then 1 else 0 end as out_flm,
    case when current_ticket.slm_opendate is not null and current_ticket.Error = 'Replace_Part' and current_ticket.aging_slm > @sla then 1 else 0 end as out_slm,
    case when current_ticket.aging_flm <= 2 then 1 else 0 end as in_flm,
    case when current_ticket.slm_opendate is not null and current_ticket.Error = 'Replace_Part' and current_ticket.aging_slm <= @sla then 1 else 0 end as in_slm
    
  from (
    select
      flm.SrNo flm_srno,
      slm.SrNo slm_srno,
      slm.IssueRefNo ,
-- flm.Description ,
      flm.status,
      slm.OpenDate as slm_opendate,
      COALESCE(slm.EquipID,flm.EquipID ) as tid,
      case when slm.SrNo is null then 'flm'
      else 'slm'
      end as tiket_type
      ,
      case
        when (flm.Description like '%%Dispenser : Hardware Fault%' or flm.Description like '%%Depository : Hardware Fault%') THEN 'DF'
        when (flm.Description like '%Cash out%' or flm.Description like '%Cash low%') THEN 'CO'
        when (flm.Description like '%Cash full%') THEN 'CF'
        when (flm.Description like '%receipt printer%') THEN 'RECEIPT'
        when flm.Description like '%##checknclean%' THEN 'cek'
         when flm.Description like '%##Replace%' THEN 'Replace_Part'
         when flm.subcalltype like '%3rdParty_Network%' THEN 'Network'
         when flm.subcalltype like '%3rdParty_Host%' THEN 'Host'
         when flm.subcalltype like '%3rdParty_Electrical%' THEN 'Electrical'
         when flm.subcalltype like '%3rdParty_Vandalism%' THEN 'Vandalism'
         when flm.subcalltype like '%3rdParty_ForceMajeure%' THEN 'ForceMajeure'
      ELSE 'NA'
      END as 'Error',
      DATEDIFF(HOUR , cast(flm.OpenDate as datetime), cast(COALESCE (COALESCE(slm.OpenDate ,flm.CloseDate),GETDATE()) as datetime)) as aging_flm,
      DATEDIFF(HOUR , cast(COALESCE(slm.OpenDate,GETDATE()) as datetime), cast(COALESCE (slm.CloseDate ,GETDATE()) as datetime)) as aging_slm
    from
      FLMCRM.dbo.servicerequest as flm
    left join
      SLMCRM.dbo.servicerequest as slm
    on
      flm.SrNo = slm.IssueRefNo
    where
      cast(flm.OpenDate as datetime) > cast(DATEADD(month, DATEDIFF(month, 0, GETDATE()), 0) as datetime)
      --flm.CloseDate is null
      and flm.area <> 'PM'
      and flm.SrNo not like '%EJ%'
      and flm.SrNo not like '%PR%'
  ) current_ticket
  left JOIN AccMast acc
  on current_ticket.tid = acc.SiteID
  where current_ticket.Error in ('DF','CO','CF','RECEIPT','cek','Replace_Part','Network','Host','Electrical','Vandalism','ForceMajeure')
) as summary
where Kanwil <> 'TEST'
and in_slm > 0 or in_flm > 0");

        $outsla = DB::connection('sqlsrv')->select("
        DECLARE @sla INT
SET @sla = 6

select
  'OUT SLA' sla,
  sum(DF) as 'DispenserFailure',
  sum(CO) as 'CashOut',
  sum(CF) as 'CashFull',
  sum(RECEIPT) as 'ReceiptLowOut',
  sum(Replace_Part) as 'ReplacePart',
  sum(cek) as 'CheckClean'
from (
  select
    acc.ZonalCode as Kanwil,
    case when current_ticket.Error = 'DF' then 1 else 0 end as 'DF',
    case when current_ticket.Error = 'CO' then 1 else 0 end as 'CO',
    case when current_ticket.Error = 'CF' then 1 else 0 end as 'CF',
    case when current_ticket.Error = 'RECEIPT' then 1 else 0 end as 'RECEIPT',
    case when current_ticket.Error = 'Replace_Part' then 1 else 0 end as 'Replace_Part',
    case when current_ticket.Error = 'cek' then 1 else 0 end as 'cek',
    case when current_ticket.Error = 'Network' then 1 else 0 end as 'Network',
    case when current_ticket.Error = 'Host' then 1 else 0 end as 'Host',
    case when current_ticket.Error = 'Electrical' then 1 else 0 end as 'Electrical',
    case when current_ticket.Error = 'Vandalism' then 1 else 0 end as 'Vandalism',
    case when current_ticket.Error = 'ForceMajeure' then 1 else 0 end as 'ForceMajeure',
    case when current_ticket.aging_flm > 2 then 1 else 0 end as out_flm,
    case when current_ticket.slm_opendate is not null and current_ticket.Error = 'Replace_Part' and current_ticket.aging_slm > @sla then 1 else 0 end as out_slm,
    case when current_ticket.aging_flm <= 2 then 1 else 0 end as in_flm,
    case when current_ticket.slm_opendate is not null and current_ticket.Error = 'Replace_Part' and current_ticket.aging_slm <= @sla then 1 else 0 end as in_slm
    
  from (
    select
      flm.SrNo flm_srno,
      slm.SrNo slm_srno,
      slm.IssueRefNo ,
-- flm.Description ,
      flm.status,
      slm.OpenDate as slm_opendate,
      COALESCE(slm.EquipID,flm.EquipID ) as tid,
      case when slm.SrNo is null then 'flm'
      else 'slm'
      end as tiket_type
      ,
      case
        when (flm.Description like '%%Dispenser : Hardware Fault%' or flm.Description like '%%Depository : Hardware Fault%') THEN 'DF'
        when (flm.Description like '%Cash out%' or flm.Description like '%Cash low%') THEN 'CO'
        when (flm.Description like '%Cash full%') THEN 'CF'
        when (flm.Description like '%receipt printer%') THEN 'RECEIPT'
        when flm.Description like '%##checknclean%' THEN 'cek'
         when flm.Description like '%##Replace%' THEN 'Replace_Part'
         when flm.subcalltype like '%3rdParty_Network%' THEN 'Network'
         when flm.subcalltype like '%3rdParty_Host%' THEN 'Host'
         when flm.subcalltype like '%3rdParty_Electrical%' THEN 'Electrical'
         when flm.subcalltype like '%3rdParty_Vandalism%' THEN 'Vandalism'
         when flm.subcalltype like '%3rdParty_ForceMajeure%' THEN 'ForceMajeure'
      ELSE 'NA'
      END as 'Error',
      DATEDIFF(HOUR , cast(flm.OpenDate as datetime), cast(COALESCE (COALESCE(slm.OpenDate ,flm.CloseDate),GETDATE()) as datetime)) as aging_flm,
      DATEDIFF(HOUR , cast(COALESCE(slm.OpenDate,GETDATE()) as datetime), cast(COALESCE (slm.CloseDate ,GETDATE()) as datetime)) as aging_slm
    from
      FLMCRM.dbo.servicerequest as flm
    left join
      SLMCRM.dbo.servicerequest as slm
    on
      flm.SrNo = slm.IssueRefNo
    where
      cast(flm.OpenDate as datetime) > cast(DATEADD(month, DATEDIFF(month, 0, GETDATE()), 0) as datetime)
      --flm.CloseDate is null
      and flm.area <> 'PM'
      and flm.SrNo not like '%EJ%'
      and flm.SrNo not like '%PR%'
  ) current_ticket
  left JOIN AccMast acc
  on current_ticket.tid = acc.SiteID
  where current_ticket.Error in ('DF','CO','CF','RECEIPT','cek','Replace_Part','Network','Host','Electrical','Vandalism','ForceMajeure')
) as summary
where Kanwil <> 'TEST'
and out_slm > 0 or out_flm > 0
        ");
        return view('admin::dashboard.chart',compact('insla','outsla'));
    }


}
