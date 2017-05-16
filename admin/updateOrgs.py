import configparser
import pymysql
import urllib.request


parser = configparser.ConfigParser()
parser.read("../halite.ini")

DB_CONFIG = parser["database"]

db = pymysql.connect(host=DB_CONFIG["hostname"], user=DB_CONFIG['username'], passwd=DB_CONFIG['password'], db=DB_CONFIG['name'], cursorclass=pymysql.cursors.DictCursor)
cursor = db.cursor()
cursor.execute("select email, userID, organization from User")
users = cursor.fetchall()

orgs = [line.strip().split(" - ") for line in open("../website/organizationWhitelist.txt").readlines()]

for user in users:
    if user["email"] == None:
        continue

    realUserOrg = "Other"
    try:
        emailDomain = user["email"].split("@")[1]
    except:
        pass
    for org in orgs:
        if emailDomain == org[1]:
            realUserOrg = org[0]
            break
    if (realUserOrg != "Other" or user["organization"] == "") and realUserOrg != user["organization"]:
        print("%s, %s, %s" % (realUserOrg, user["organization"], user["email"]))
        cursor.execute("update User set organization = '"+realUserOrg+"' where userID="+str(user["userID"]))
        db.commit()
