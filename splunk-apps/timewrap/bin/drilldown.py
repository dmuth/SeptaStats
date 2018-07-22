# Copyright (C) 2005-2014 Splunk Inc. All Rights Reserved.  Version 4.0
import re, sys, time, csv, traceback
import splunk.Intersplunk as si
import splunk.util
import urllib

def run(results, fields):
    
    try:
        values = set()
        for result in results:
            field = None
            for f,v in result.items():
                if f not in ['count','percent']:
                    field = f
                    break
            else:
                continue
            value = result[field]
            if value.lower() == "other":
                value = ' '.join(['NOT %s="%s" ' % (field, v.replace('"','\\"')) for v in values]) + ' %s=*' % field
            elif value.lower() == "null":
                value = 'NOT %s=*' % field
            else:
                values.add(value)
                value = '%s="%s"' % (field, v.replace('"','\\"'))

            result['_drilldown'] = value

        if '_drilldown' not in fields:
            fields.append('_drilldown')

        si.outputResults(results, {}, fields=fields)
    except Exception, e2:
        stack2 =  traceback.format_exc()
        si.generateErrorResults("Error '%s'. %s" % (e2, stack2))



#--------------------------------------------------------------------------------------------------
# COPIED AND MODIFIED THIS SECTION FROM INTERSPLUNK BECAUSE WE NEED TO GET COLUMN ORDERS

MV_ENABLED = True

def readResults(input_buf = None, settings = None, has_header = True):
    '''
    Converts an Intersplunk-formatted file object into a dict
    representation of the contained events.
    '''
    
    if input_buf == None:
        input_buf = sys.stdin

    results = []

    if settings == None:
        settings = {} # dummy

    if has_header:
        # until we get a blank line, read "attr:val" lines, setting the values in 'settings'
        attr = last_attr = None
        while True:
            line = input_buf.readline()
            line = line[:-1] # remove lastcharacter(newline)
            if len(line) == 0:
                break

            colon = line.find(':')
            if colon < 0:
                if last_attr:
                   settings[attr] = settings[attr] + '\n' + urllib.unquote(line)
                else:
                   continue

            # extract it and set value in settings
            last_attr = attr = line[:colon]
            val  = urllib.unquote(line[colon+1:])
            settings[attr] = val

    csvr = csv.reader(input_buf)
    header = []
    first = True
    mv_fields = []
    for line in csvr:
        if first:
            header = line
            first = False
            # Check which fields are multivalued (for a field 'foo', '__mv_foo' also exists)
            if MV_ENABLED:
                for field in header:
                    if "__mv_" + field in header:
                        mv_fields.append(field)
            continue

        # need to maintain field order
        result = splunk.util.OrderedDict()
        i = 0
        for val in line:
            result[header[i]] = val
            i = i+1

        for key in mv_fields:
            mv_key = "__mv_" + key
            if key in result and mv_key in result:
                # Expand the value of __mv_[key] to a list, store it in key, and delete __mv_[key]
                vals = []
                if decodeMV(result[mv_key], vals):
                    result[key] = copy.deepcopy(vals)
                    if len(result[key]) == 1:
                        result[key] = result[key][0]
                    del result[mv_key]

        results.append(result)

    return results, header


def getOrganizedResults(input_str = None):
    '''
    Converts an Intersplunk-formatted file object into a dict
    representation of the contained events, and returns a tuple of:
    
        (results, dummyresults, settings)
        
    "dummyresults" is always an empty list, and "settings" is always
    an empty dict, since the change to csv stopped sending the
    searchinfo.  It has not been updated to store the auth token.
    '''

    settings = {}
    dummyresults = []

    results, fields = readResults(input_str, settings)

    return results, dummyresults, settings, fields
 
# -------------------------------------------

def usage():
    si.generateErrorResults("not implimented")
    exit(-1)

if __name__ == '__main__':
    try:
        (isgetinfo, sys.argv) = si.isGetInfo(sys.argv)
        argc = len(sys.argv)
        if isgetinfo:
            #  outputInfo(streaming, generating, retevs, reqsop, preop, timeorder=False, clear_req_fields=False, req_fields = None)
            si.outputInfo(False,      False,     False,   False, None, timeorder=False)

        results, dummyresults, settings, fields = getOrganizedResults()
        run(results, fields)
    except Exception, e:
        raise
