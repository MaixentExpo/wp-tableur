


select tableur_contacts.name
from tableur_contacts
where tableur_contacts.name like '%Presse%'
or tableur_contacts.id in 
(
  select tableur_affectations.contact_id
  from tableur_affectations
  where tableur_affectations.commission_id in 
  (
    select tableur_commissions.id
    from tableur_commissions
    where tableur_commissions.name = 'Presse'
  )
)

ALTER TABLE tableur_contacts ADD COLUMN commission VARCHAR(250) NULL;
ALTER TABLE tableur_contacts CHANGE profil role VARCHAR(50)

select tableur_contacts.* from tableur_contacts 
inner join tbr_categories as cat 
on cat.link_id = tableur_contacts.id and cat.name = 'tableur_contacts.commission'
and cat_id = 13
