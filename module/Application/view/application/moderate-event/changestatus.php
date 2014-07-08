<form action="" method="post" name="chgEventStat">

<input name="active" type="button"><input name="inactive" type="button">

<select name="approveEvent">
  <option value="0">select reason</option>
<option value="admin approved">admin approved</option>
<option value="subscription update">subscription update</option>

</select>
<select name="disapproveEvent">
<option value="admin disapprove">admin disapprove</option>
  <option value="subscription expired">subscription expired</option>
    <option value="user objection">user objection</option>


</select>

<input name="event_id" type="hidden">
</form>